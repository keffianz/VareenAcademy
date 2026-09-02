<?php
/**
 * Certificate Class - issue, list and verify course-completion certificates.
 * Certificates are auto-issued when a student completes every active lesson
 * of an enrolled course. Codes are unguessable and used for public /verify.
 */

require_once 'Database.php';

class Certificate {
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    /**
     * Issue a certificate if the student completed 100% of the course's
     * active lessons. Safe to call repeatedly: returns the existing
     * certificate instead of duplicating.
     */
    public function issueIfEligible($student_id, $course_id) {
        $student_id = (int)$student_id;
        $course_id  = (int)$course_id;

        try {
            // Must be an active enrollment in an active course
            $stmt = $this->db->prepare(
                'SELECT e.id FROM enrollments e
                 JOIN courses c ON c.id = e.course_id
                 WHERE e.student_id = :s AND e.course_id = :c AND e.status != "dropped" AND c.is_active = 1'
            );
            $stmt->execute([':s' => $student_id, ':c' => $course_id]);
            if (!$stmt->fetchColumn()) {
                return ['success' => false, 'message' => 'Not enrolled'];
            }

            // All active lessons must be completed
            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM lessons WHERE course_id = :c AND is_active = 1'
            );
            $stmt->execute([':c' => $course_id]);
            $total = (int)$stmt->fetchColumn();
            if ($total === 0) {
                return ['success' => false, 'message' => 'Course has no lessons yet'];
            }

            $stmt = $this->db->prepare(
                'SELECT COUNT(*) FROM lesson_progress lp
                 JOIN lessons l ON l.id = lp.lesson_id
                 WHERE lp.student_id = :s AND l.course_id = :c AND l.is_active = 1 AND lp.is_completed = 1'
            );
            $stmt->execute([':s' => $student_id, ':c' => $course_id]);
            $completed = (int)$stmt->fetchColumn();

            if ($completed < $total) {
                return ['success' => false, 'message' => 'Course not yet completed'];
            }

            // Already issued?
            $existing = $this->getByStudentCourse($student_id, $course_id);
            if ($existing) {
                return ['success' => true, 'issued' => false, 'certificate' => $existing];
            }

            return $this->issue($student_id, $course_id);
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Certificate check failed'];
        }
    }

    /**
     * Force-issue a certificate (admin action). Skips if one exists.
     */
    public function issue($student_id, $course_id) {
        try {
            $existing = $this->getByStudentCourse((int)$student_id, (int)$course_id);
            if ($existing) {
                return ['success' => true, 'issued' => false, 'certificate' => $existing];
            }

            $code = $this->generateCode();
            $stmt = $this->db->prepare(
                'INSERT INTO certificates (certificate_code, student_id, course_id, issued_at)
                 VALUES (:code, :s, :c, NOW())'
            );
            $stmt->execute([':code' => $code, ':s' => (int)$student_id, ':c' => (int)$course_id]);
            $certificate = $this->getByCode($code);

            return ['success' => true, 'issued' => true, 'certificate' => $certificate];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Certificate issue failed'];
        }
    }

    /**
     * Public verification lookup by code. Returns only non-sensitive data.
     */
    public function verifyByCode($code) {
        $code = strtoupper(trim($code));
        if ($code === '' || !preg_match('/^[A-Z0-9\-]{6,32}$/', $code)) {
            return null;
        }
        try {
            $stmt = $this->db->prepare(
                'SELECT cert.certificate_code, cert.issued_at, cert.revoked, cert.revoked_at,
                        CONCAT(u.first_name, " ", u.last_name) AS student_name,
                        c.title AS course_title, c.category AS course_category
                 FROM certificates cert
                 JOIN users u ON u.id = cert.student_id
                 JOIN courses c ON c.id = cert.course_id
                 WHERE cert.certificate_code = :code LIMIT 1'
            );
            $stmt->execute([':code' => $code]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * All certificates for one student (their certificates page).
     */
    public function listForStudent($student_id) {
        try {
            $stmt = $this->db->prepare(
                'SELECT cert.id, cert.certificate_code, cert.issued_at, cert.revoked,
                        c.title AS course_title, c.category AS course_category
                 FROM certificates cert
                 JOIN courses c ON c.id = cert.course_id
                 WHERE cert.student_id = :s
                 ORDER BY cert.issued_at DESC'
            );
            $stmt->execute([':s' => (int)$student_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getByStudentCourse($student_id, $course_id) {
        try {
            $stmt = $this->db->prepare(
                'SELECT id, certificate_code, issued_at, revoked FROM certificates
                 WHERE student_id = :s AND course_id = :c LIMIT 1'
            );
            $stmt->execute([':s' => (int)$student_id, ':c' => (int)$course_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function getByCode($code) {
        try {
            $stmt = $this->db->prepare('SELECT * FROM certificates WHERE certificate_code = :code LIMIT 1');
            $stmt->execute([':code' => $code]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Admin: revoke / restore a certificate.
     */
    public function setRevoked($certificate_id, $revoked) {
        try {
            $stmt = $this->db->prepare(
                'UPDATE certificates SET revoked = :r, revoked_at = ' . ($revoked ? 'NOW()' : 'NULL') . '
                 WHERE id = :id'
            );
            $stmt->execute([':r' => (int)(bool)$revoked, ':id' => (int)$certificate_id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Admin: issue on behalf of a student (matched by email + student role).
     */
    public function issueForStudentEmail($email, $course_id, $role = 'student') {
        $email = strtolower(trim($email));
        $course_id = (int)$course_id;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$course_id) {
            return ['success' => false, 'message' => 'Valid student email and course are required'];
        }
        try {
            $stmt = $this->db->prepare('SELECT id FROM users WHERE email = :e AND role = :r LIMIT 1');
            $stmt->execute([':e' => $email, ':r' => $role]);
            $studentId = $stmt->fetchColumn();
            if (!$studentId) {
                return ['success' => false, 'message' => 'No student account found with that email'];
            }
            $result = $this->issue((int)$studentId, $course_id);
            if ($result['success'] && $result['issued']) {
                return ['success' => true, 'message' => 'Certificate issued', 'certificate' => $result['certificate']];
            }
            if ($result['success']) {
                return ['success' => false, 'message' => 'A certificate already exists for that student and course'];
            }
            return $result;
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Certificate issue failed'];
        }
    }

    /**
     * Unguessable public code, e.g. VER-7K2M9F-QX4T8B
     */
    private function generateCode() {
        $alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ'; // no easily-confused characters
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $a = $this->randomBlock($alphabet, 6);
            $b = $this->randomBlock($alphabet, 6);
            $code = "VER-{$a}-{$b}";
            if (!$this->getByCode($code)) {
                return $code;
            }
        }
        // Practically unreachable; fall back to stronger entropy
        return 'VER-' . strtoupper(bin2hex(random_bytes(8)));
    }

    private function randomBlock($alphabet, $length) {
        $max = strlen($alphabet) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }
        return $out;
    }
}
