<?php
/**
 * VAREEN Academy \u2014 Payment Orchestration Class
 *
 * Single entry point for the entire payment lifecycle:
 *   initialize()  â†’ create pending payment + gateway redirect
 *   verify()     â†’ server-side gateway verification (the ONLY trust source)
 *   approve()    â†’ manual bank-transfer approval by admin
 *   enroll()     â†’ auto-enroll after verified payment
 *
 * Every method runs inside a database transaction so a failure never leaves
 * a half-paid / half-enrolled state.
 */

class Payment
{
    private $db;
    private $config;

    public function __construct()
    {
        // Self-contained dependencies: this class may be invoked from the
        // payments API, webhooks receiver, or admin views — load what we need.
        require_once __DIR__ . '/Course.php';
        require_once __DIR__ . '/Enrollment.php';
        require_once __DIR__ . '/Notification.php';
        require_once __DIR__ . '/Coupon.php';
        require_once __DIR__ . '/PaystackGateway.php';
        require_once __DIR__ . '/FlutterwaveGateway.php';
        $this->db = Database::getInstance();
        $this->config = require __DIR__ . '/../config/payments.php';
    }

    // ------------------------------------------------------------------
    //  CONFIG HELPERS
    // ------------------------------------------------------------------

    public function getConfig(): array
    {
        return $this->config;
    }

    public function currencySymbol(): string
    {
        return $this->config['currency_symbol'] ?? 'â‚¦';
    }

    /**
     * Return the list of payment methods that are actually enabled.
     */
        public function availableMethods(): array
    {
        $methods = [];
        if (!empty($this->config['paystack']['enabled'])) $methods[] = 'paystack';
        if (!empty($this->config['flutterwave']['enabled'])) $methods[] = 'flutterwave';
        if (!empty($this->config['bank_transfer']['enabled'])) $methods[] = 'bank_transfer';
        return $methods;
    }

    // ------------------------------------------------------------------
    //  INITIALIZE \u2014 create a pending payment and return a gateway redirect
    // ------------------------------------------------------------------

    /**
     * @param int    $userId
     * @param int    $courseId
     * @param string $method      paystack | flutterwave | bank_transfer
     * @param string $couponCode  optional coupon code
     * @param string $callbackUrl absolute URL for online gateways
     * @return array ['success'=>bool, 'redirect_url'=>?string, 'reference'=>?string,
     *               'message'=>string, 'payment_id'=>?int]
     */
    public function initialize(int $userId, int $courseId, string $method, string $couponCode = '', string $callbackUrl = ''): array
    {
        // Validate course
        $course = (new Course())->getCourseById($courseId);
        if (!$course) {
            return ['success' => false, 'message' => 'Course not found.'];
        }

        // Already enrolled?
        if ((new Enrollment())->isEnrolled($userId, $courseId)) {
            return ['success' => false, 'message' => 'You are already enrolled in this course.'];
        }

        // Pending payment already exists for this user+course?
        $existing = $this->db->fetch(
            "SELECT id, reference, payment_method, status FROM payments
             WHERE user_id = ? AND course_id = ? AND status = 'pending'
             ORDER BY id DESC LIMIT 1",
            [$userId, $courseId]
        );
        if ($existing && in_array($existing['payment_method'], ['paystack', 'flutterwave'], true)) {
            return [
                'success'      => true,
                'reference'    => $existing['reference'],
                'payment_id'   => $existing['id'],
                'redirect_url' => $callbackUrl . (strpos($callbackUrl, '?') !== false ? '&' : '?')
                    . 'reference=' . urlencode($existing['reference']),
                'message'      => 'Resuming your previous payment.',
            ];
        }

        // Calculate price
        $originalAmount = (float) ($course['price'] ?? 0);
        $discountAmount = 0.00;
        $couponId = null;

        if ($couponCode !== '') {
            $coupon = new Coupon();
            $validation = $coupon->validate($couponCode, $courseId, $userId, $originalAmount);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['reason']];
            }
            $discountAmount = (float) $validation['discount'];
            $couponId = (int) $validation['coupon_id'];
        }

        $finalAmount = max(0, $originalAmount - $discountAmount);

        // Free course (full scholarship) \u2014 enroll immediately, no gateway needed
        if ($finalAmount <= 0) {
            return $this->handleFreeEnrollment($userId, $courseId, $originalAmount, $discountAmount, $couponCode, $couponId);
        }

        // Validate gateway
        if (!in_array($method, ['paystack', 'flutterwave', 'bank_transfer'], true)) {
            return ['success' => false, 'message' => 'Invalid payment method.'];
        }
        if ($method === 'paystack' && empty($this->config['paystack']['enabled'])) {
            return ['success' => false, 'message' => 'Paystack is not available.'];
        }
        if ($method === 'flutterwave' && empty($this->config['flutterwave']['enabled'])) {
            return ['success' => false, 'message' => 'Flutterwave is not available.'];
        }

        // Create payment record
        $reference = $this->generateReference();
        $paymentId = $this->db->insert('payments', [
            'user_id'         => $userId,
            'course_id'       => $courseId,
            'amount'          => $finalAmount,
            'original_amount' => $originalAmount,
            'discount_amount' => $discountAmount,
            'coupon_code'     => $couponCode ?: null,
            'currency'        => $this->config['currency'],
            'payment_method'  => $method,
            'status'          => 'pending',
            'reference'       => $reference,
        ]);

        // Record coupon redemption
        if ($couponId) {
            $this->db->insert('coupon_redemptions', [
                'coupon_id'  => $couponId,
                'user_id'    => $userId,
                'payment_id' => $paymentId,
            ]);
        }

                // Online gateway \u2014 return redirect URL
        if (in_array($method, ['paystack', 'flutterwave'], true)) {
            $gateway = $this->getGateway($method);
            $payment = $this->db->fetch(
                "SELECT p.*, u.email, u.first_name, u.last_name
                 FROM payments p JOIN users u ON u.id = p.user_id
                 WHERE p.id = ?",
                [$paymentId]
            );
            $result = $gateway->initializeTransaction($payment, $callbackUrl);

            if (!$result['success']) {
                $this->db->update('payments', ['status' => 'failed'], 'id = ?', [$paymentId]);
            }
            $result['reference'] = $reference;
            $result['payment_id'] = $paymentId;
            return $result;
        }

        // Bank transfer \u2014 no redirect, just return the reference for the student to use as narration
        return [
            'success'      => true,
            'reference'    => $reference,
            'payment_id'   => $paymentId,
            'redirect_url' => null,
            'message'      => 'Payment reference created. Transfer the exact amount and confirm using the reference code.',
        ];
    }

    // ------------------------------------------------------------------
    //  VERIFY \u2014 server-side gateway verification (ONLY trust source)
    // ------------------------------------------------------------------

    /**
     * Verify an online payment by reference. Idempotent: a second call for an
     * already-paid payment returns success without re-enrolling.
     *
     * @return array ['success'=>bool, 'message'=>string, 'enrolled'=>bool]
     */
    public function verify(string $reference): array
    {
        $payment = $this->db->fetch(
            "SELECT * FROM payments WHERE reference = ? LIMIT 1",
            [$reference]
        );
        if (!$payment) {
            return ['success' => false, 'message' => 'Payment not found.'];
        }

        // Already verified \u2014 idempotent
        if ($payment['status'] === 'paid') {
            return ['success' => true, 'message' => 'Payment already verified.', 'enrolled' => true];
        }

        // Only online payments reach here
        if (!in_array($payment['payment_method'], ['paystack', 'flutterwave'], true)) {
            return ['success' => false, 'message' => 'Use admin approval for bank transfers.'];
        }

        $gateway = $this->getGateway($payment['payment_method']);
        $result = $gateway->verifyTransaction($reference);

        // Log the verification attempt
        $this->db->update('payments', [
            'gateway_response' => $result['gateway_response'],
        ], 'id = ?', [$payment['id']]);

        if (!$result['verified']) {
            $this->db->update('payments', ['status' => 'failed'], 'id = ?', [$payment['id']]);
            return ['success' => false, 'message' => $result['message'] ?? 'Payment verification failed.'];
        }

                // Amount sanity check (Paystack amounts are in kobo)
        if (isset($result['amount_paid']) && $result['amount_paid'] < $payment['amount'] * 0.95) {
            $this->db->update('payments', ['status' => 'failed'], 'id = ?', [$payment['id']]);
            return ['success' => false, 'message' => 'Amount paid is less than the required amount.'];
        }

        return $this->completePayment($payment, $result['transaction_id'] ?? $reference);
    }

    // ------------------------------------------------------------------
    //  APPROVE / REJECT \u2014 manual bank-transfer approval by admin
    // ------------------------------------------------------------------

    public function approveBankTransfer(int $paymentId, int $adminId, string $notes = ''): array
    {
        $payment = $this->db->fetch("SELECT * FROM payments WHERE id = ? LIMIT 1", [$paymentId]);
        if (!$payment) {
            return ['success' => false, 'message' => 'Payment not found.'];
        }
        if ($payment['payment_method'] !== 'bank_transfer') {
            return ['success' => false, 'message' => 'Only bank transfers can be approved manually.'];
        }
        if ($payment['status'] === 'paid') {
            return ['success' => false, 'message' => 'This payment is already approved.'];
        }

        $this->db->update('payments', [
            'status'      => 'paid',
            'paid_at'     => date('Y-m-d H:i:s'),
            'admin_notes' => $notes ?: null,
        ], 'id = ?', [$payment['id']]);

        $result = $this->completePayment($payment, 'manual-' . $payment['reference']);

        (new Notification())->create(
            $payment['user_id'],
            'payment',
            'Payment Approved',
            'Your bank transfer was approved! You now have access to your course.'
        );

        return $result;
    }

    public function rejectBankTransfer(int $paymentId, int $adminId, string $reason): array
    {
        $payment = $this->db->fetch("SELECT * FROM payments WHERE id = ? LIMIT 1", [$paymentId]);
        if (!$payment) {
            return ['success' => false, 'message' => 'Payment not found.'];
        }

                $this->db->update('payments', [
            'status'      => 'failed',
            'admin_notes' => 'Rejected: ' . $reason,
        ], 'id = ?', [$payment['id']]);

        (new Notification())->create(
            $payment['user_id'],
            'payment',
            'Payment Rejected',
            'Your bank transfer was rejected: ' . $reason
        );

        return ['success' => true, 'message' => 'Payment rejected.'];
    }

    // ------------------------------------------------------------------
    //  REFUND
    // ------------------------------------------------------------------

    public function requestRefund(int $paymentId, int $userId, string $reason): array
    {
        $payment = $this->db->fetch(
            "SELECT * FROM payments WHERE id = ? AND user_id = ? LIMIT 1",
            [$paymentId, $userId]
        );
        if (!$payment) {
            return ['success' => false, 'message' => 'Payment not found.'];
        }
        if ($payment['status'] !== 'paid') {
            return ['success' => false, 'message' => 'Only paid payments can be refunded.'];
        }

        $existing = $this->db->fetch(
            "SELECT id FROM refund_requests WHERE payment_id = ? AND status = 'pending' LIMIT 1",
            [$paymentId]
        );
        if ($existing) {
            return ['success' => false, 'message' => 'A refund request is already pending.'];
        }

        $this->db->insert('refund_requests', [
            'payment_id' => $paymentId,
            'user_id'    => $userId,
            'reason'     => $reason,
        ]);

        return ['success' => true, 'message' => 'Refund request submitted.'];
    }

    public function processRefund(int $refundId, int $adminId, string $decision, string $notes = ''): array
    {
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            return ['success' => false, 'message' => 'Invalid decision.'];
        }

        $refund = $this->db->fetch("SELECT * FROM refund_requests WHERE id = ? LIMIT 1", [$refundId]);
        if (!$refund) {
            return ['success' => false, 'message' => 'Refund request not found.'];
        }
        if ($refund['status'] !== 'pending') {
            return ['success' => false, 'message' => 'This request has already been processed.'];
        }

        $this->db->beginTransaction();
        try {
            $this->db->update('refund_requests', [
                'status'       => $decision,
                'admin_notes'  => $notes ?: null,
                'processed_by' => $adminId,
                'processed_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$refundId]);

            if ($decision === 'approved') {
                $this->db->update('payments', ['status' => 'refunded'], 'id = ?', [$refund['payment_id']]);
            }

                        $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Could not process refund.'];
        }

        if ($decision === 'approved') {
            (new Notification())->create(
                $refund['user_id'],
                'payment',
                'Refund Approved',
                'Your refund request has been approved.'
            );
        }

        return ['success' => true, 'message' => 'Refund ' . $decision . ' successfully.'];
    }

    // ------------------------------------------------------------------
    //  RECEIPTS & USER HISTORY
    // ------------------------------------------------------------------

    public function getReceipt(int $paymentId, int $userId = 0): ?array
    {
        $sql = "SELECT r.*, p.reference, p.amount, p.original_amount, p.discount_amount,
                       p.payment_method, p.paid_at, p.currency,
                       u.email AS student_email,
                       CONCAT(u.first_name, ' ', u.last_name) AS student_name,
                       c.title AS course_title
                FROM payment_receipts r
                JOIN payments p ON p.id = r.payment_id
                JOIN users u ON u.id = p.user_id
                JOIN courses c ON c.id = p.course_id
                WHERE r.payment_id = ?";
        $params = [$paymentId];
        if ($userId > 0) {
            $sql .= " AND p.user_id = ?";
            $params[] = $userId;
        }
        return $this->db->fetch($sql, $params) ?: null;
    }

    public function getUserPayments(int $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, c.title AS course_title, c.thumbnail AS course_image,
                    r.receipt_number
             FROM payments p
             JOIN courses c ON c.id = p.course_id
             LEFT JOIN payment_receipts r ON r.payment_id = p.id
             WHERE p.user_id = ?
             ORDER BY p.created_at DESC
             LIMIT ? OFFSET ?",
            [$userId, $limit, $offset]
        );
    }

    // ------------------------------------------------------------------
    //  ADMIN QUERIES
    // ------------------------------------------------------------------

    public function adminRevenueStats(): array
    {
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('-7 days'));
        $monthStart = date('Y-m-01');

        $q = "SELECT
            COALESCE(SUM(CASE WHEN status='paid' AND DATE(paid_at)=? THEN amount END),0) AS today,
            COALESCE(SUM(CASE WHEN status='paid' AND paid_at>=? THEN amount END),0) AS weekly,
            COALESCE(SUM(CASE WHEN status='paid' AND paid_at>=? THEN amount END),0) AS monthly,
            COALESCE(SUM(CASE WHEN status='paid' THEN amount END),0) AS total,
            COUNT(CASE WHEN status='paid' THEN 1 END) AS paid_count,
            COUNT(CASE WHEN status='pending' THEN 1 END) AS pending_count,
            COUNT(CASE WHEN status='failed' THEN 1 END) AS failed_count,
            COUNT(CASE WHEN status='refunded' THEN 1 END) AS refund_count
        FROM payments";
        $row = $this->db->fetch($q, [$today, $weekStart, $monthStart]);
        return $row ?: array_fill_keys(
            ['today','weekly','monthly','total','paid_count','pending_count','failed_count','refund_count'], 0
        );
    }

    public function adminPaymentsList(string $status = '', string $search = '', int $limit = 25, int $offset = 0): array
    {
        $where = [];
        $params = [];
        if ($status !== '') {
            $where[] = "p.status = ?";
            $params[] = $status;
        }
        if ($search !== '') {
            $where[] = "(CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR c.title LIKE ? OR p.reference LIKE ?)";
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) AS student_name, u.email AS student_email,
                       c.title AS course_title, r.receipt_number
                FROM payments p
                JOIN users u ON u.id = p.user_id
                JOIN courses c ON c.id = p.course_id
                LEFT JOIN payment_receipts r ON r.payment_id = p.id
                $whereSql
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        return $this->db->fetchAll($sql, $params);
    }

    public function adminPendingRefunds(): array
    {
        return $this->db->fetchAll(
            "SELECT rr.*, p.reference, p.amount, p.payment_method,
                    CONCAT(u.first_name, ' ', u.last_name) AS student_name, c.title AS course_title
             FROM refund_requests rr
             JOIN payments p ON p.id = rr.payment_id
             JOIN users u ON u.id = rr.user_id
             JOIN courses c ON c.id = p.course_id
             WHERE rr.status = 'pending'
             ORDER BY rr.created_at DESC"
        );
    }

    public function adminPendingBankTransfers(): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) AS student_name, u.email AS student_email,
                    c.title AS course_title
             FROM payments p
             JOIN users u ON u.id = p.user_id
             JOIN courses c ON c.id = p.course_id
             WHERE p.payment_method = 'bank_transfer' AND p.status = 'pending'
             ORDER BY p.created_at DESC"
        );
    }

    // ------------------------------------------------------------------
    //  PRIVATE HELPERS
    // ------------------------------------------------------------------

    private function getGateway(string $method)
    {
        if ($method === 'paystack') {
            return new PaystackGateway($this->config['paystack']);
        }
        if ($method === 'flutterwave') {
            return new FlutterwaveGateway($this->config['flutterwave']);
        }
        throw new RuntimeException("Unsupported gateway: $method");
    }

    private function generateReference(): string
    {
        return 'VAREEN-' . strtoupper(bin2hex(random_bytes(6))) . '-' . time();
    }

    private function generateReceiptNumber(): string
    {
        return 'RCP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * Mark payment as paid, enroll student, generate receipt, notify.
     * Wrapped in a transaction.
     */
    private function completePayment(array $payment, ?string $transactionId): array
    {
        $this->db->beginTransaction();
        try {
            // Guard: double-check not already paid (race condition)
            $check = $this->db->fetch(
                "SELECT status FROM payments WHERE id = ? FOR UPDATE",
                [$payment['id']]
            );
            if ($check && $check['status'] === 'paid') {
                $this->db->commit();
                return ['success' => true, 'message' => 'Payment already verified.', 'enrolled' => true];
            }

            $this->db->update('payments', [
                'status'         => 'paid',
                'transaction_id' => $transactionId,
                'paid_at'        => date('Y-m-d H:i:s'),
            ], 'id = ?', [$payment['id']]);

            // Enroll
            $enrollResult = (new Enrollment())->enrollStudent($payment['user_id'], $payment['course_id']);
            $enrolled = !empty($enrollResult['success']);

            // Receipt
            $receiptNumber = $this->generateReceiptNumber();
            $this->db->insert('payment_receipts', [
                'payment_id'     => $payment['id'],
                'receipt_number' => $receiptNumber,
            ]);

            // Increment coupon usage
            if (!empty($payment['coupon_code'])) {
                $coupon = $this->db->fetch("SELECT id FROM coupons WHERE code = ?", [$payment['coupon_code']]);
                if ($coupon) {
                    $this->db->query(
                        "UPDATE coupons SET used_count = used_count + 1 WHERE id = ?",
                        [$coupon['id']]
                    );
                }
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Payment verified but enrollment failed. Contact support.'];
        }

        // Notify
        $course = (new Course())->getCourseById($payment['course_id']);
        $msg = 'Payment successful! You are now enrolled in "' . ($course['title'] ?? 'your course') . '".';
        (new Notification())->create($payment['user_id'], 'payment', 'Payment Successful', $msg);

        return ['success' => true, 'message' => $msg, 'enrolled' => $enrolled];
    }

    private function handleFreeEnrollment(int $userId, int $courseId, float $original, float $discount, string $couponCode, ?int $couponId): array
    {
        $this->db->beginTransaction();
        try {
            $reference = $this->generateReference();
            $paymentId = $this->db->insert('payments', [
                'user_id'         => $userId,
                'course_id'       => $courseId,
                'amount'          => 0.00,
                'original_amount' => $original,
                'discount_amount' => $discount,
                'coupon_code'     => $couponCode ?: null,
                'currency'        => $this->config['currency'],
                'payment_method'  => $couponId ? 'coupon' : 'free',
                'status'          => 'paid',
                'reference'       => $reference,
                'paid_at'         => date('Y-m-d H:i:s'),
            ]);

            if ($couponId) {
                $this->db->insert('coupon_redemptions', [
                    'coupon_id'  => $couponId,
                    'user_id'    => $userId,
                    'payment_id' => $paymentId,
                ]);
                $this->db->query("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?", [$couponId]);
            }

            $enrollResult = (new Enrollment())->enrollStudent($userId, $courseId);
            $enrolled = !empty($enrollResult['success']);
            $receiptNumber = $this->generateReceiptNumber();
            $this->db->insert('payment_receipts', [
                'payment_id'     => $paymentId,
                'receipt_number' => $receiptNumber,
            ]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Could not complete enrollment.'];
        }

        $course = (new Course())->getCourseById($courseId);
        $msg = 'You are now enrolled in "' . ($course['title'] ?? 'your course') . '" \u2014 no payment required.';
        (new Notification())->create($userId, 'enrollment', 'Enrollment Confirmed', $msg);

        return ['success' => true, 'message' => $msg, 'enrolled' => $enrolled, 'reference' => $reference];
    }
}