<?php
/**
 * Coupon / Scholarship engine.
 * Handles validation, application, usage tracking and redemption logging.
 */
require_once 'Database.php';

class Coupon {
    private $db;
    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    /**
     * Validate a coupon against a course + user, returning discount breakdown.
     */
    public function validate(string $code, int $courseId, int $userId, float $originalAmount): array {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return ['valid' => false, 'reason' => 'No coupon applied.'];
        }
        $stmt = $this->db->prepare("SELECT * FROM coupons WHERE code = :code LIMIT 1");
        $stmt->execute([':code' => $code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$coupon)                return ['valid' => false, 'reason' => 'Coupon code not found.'];
        if (!(int) $coupon['active'])return ['valid' => false, 'reason' => 'This coupon is no longer active.'];
        if (!empty($coupon['expires_at']) && strtotime($coupon['expires_at']) < time())
            return ['valid' => false, 'reason' => 'This coupon has expired.'];
        if ($coupon['course_id'] !== null && (int) $coupon['course_id'] !== $courseId)
            return ['valid' => false, 'reason' => 'This coupon does not apply to this course.'];
        if ($coupon['max_uses'] !== null && (int) $coupon['used_count'] >= (int) $coupon['max_uses'])
            return ['valid' => false, 'reason' => 'This coupon has reached its usage limit.'];

        $stmt2 = $this->db->prepare("SELECT id FROM coupon_redemptions WHERE coupon_id = :cid AND user_id = :uid LIMIT 1");
        $stmt2->execute([':cid' => $coupon['id'], ':uid' => $userId]);
        if ($stmt2->fetch()) return ['valid' => false, 'reason' => 'You have already used this coupon.'];

        $discount = 0.0;
        if ($coupon['type'] === 'percentage') {
            $discount = round(((float) $coupon['value'] / 100) * $originalAmount, 2);
        } elseif ($coupon['type'] === 'fixed') {
            $discount = min((float) $coupon['value'], $originalAmount);
        } elseif ($coupon['type'] === 'full_scholarship') {
            $discount = $originalAmount;
        }
        $finalAmount = max(0, round($originalAmount - $discount, 2));

        return [
            'valid'     => true,
            'coupon_id' => (int) $coupon['id'],
            'code'      => $coupon['code'],
            'type'      => $coupon['type'],
            'value'     => (float) $coupon['value'],
            'discount'  => $discount,
            'original'  => $originalAmount,
            'final'     => $finalAmount,
        ];
    }

    /** Record redemption + bump usage count atomically. */
    public function recordRedemption(int $couponId, int $userId, int $paymentId): bool {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("INSERT INTO coupon_redemptions (coupon_id, user_id, payment_id) VALUES (:cid,:uid,:pid)");
            $stmt->execute([':cid' => $couponId, ':uid' => $userId, ':pid' => $paymentId]);
            $upd = $this->db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = :id");
            $upd->execute([':id' => $couponId]);
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function listAll(): array {
        return $this->db->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM coupons WHERE id=:id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): array {
        $stmt = $this->db->prepare(
            "INSERT INTO coupons (code, type, value, course_id, max_uses, expires_at, active)
             VALUES (:code, :type, :value, :cid, :max, :exp, :active)"
        );
        try {
            $stmt->execute([
                ':code'   => strtoupper(trim($data['code'])),
                ':type'   => $data['type'],
                ':value'  => (float) ($data['value'] ?? 0),
                ':cid'    => !empty($data['course_id']) ? (int) $data['course_id'] : null,
                ':max'    => !empty($data['max_uses']) ? (int) $data['max_uses'] : null,
                ':exp'    => !empty($data['expires_at']) ? $data['expires_at'] : null,
                ':active' => isset($data['active']) ? (int) $data['active'] : 1,
            ]);
            return ['success' => true, 'id' => (int) $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Could not create coupon: ' . $e->getMessage()];
        }
    }

    public function update(int $id, array $data): array {
        $stmt = $this->db->prepare(
            "UPDATE coupons SET type=:type, value=:value, course_id=:cid, max_uses=:max, expires_at=:exp, active=:active WHERE id=:id"
        );
        try {
            $stmt->execute([
                ':type' => $data['type'], ':value' => (float) ($data['value'] ?? 0),
                ':cid' => !empty($data['course_id']) ? (int) $data['course_id'] : null,
                ':max' => !empty($data['max_uses']) ? (int) $data['max_uses'] : null,
                ':exp' => !empty($data['expires_at']) ? $data['expires_at'] : null,
                ':active' => isset($data['active']) ? (int) $data['active'] : 1, ':id' => $id,
            ]);
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function toggle(int $id): array {
        $this->db->prepare("UPDATE coupons SET active = !active WHERE id=:id")->execute([':id' => $id]);
        return ['success' => true];
    }

    public function delete(int $id): array {
        $this->db->prepare("DELETE FROM coupons WHERE id=:id")->execute([':id' => $id]);
        return ['success' => true];
    }
}
