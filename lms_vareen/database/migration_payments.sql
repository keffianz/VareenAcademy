-- ============================================================
-- VAREEN Academy LMS — Payment System Migration
-- Adds: payments, payment_receipts, payment_webhooks, coupons,
--       coupon_redemptions, refund_requests
-- Safe to run once on an existing database.
-- ============================================================

CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    original_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    coupon_code VARCHAR(50) NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'NGN',
    payment_method ENUM('paystack','flutterwave','bank_transfer','free','coupon') NOT NULL DEFAULT 'paystack',
    status ENUM('pending','paid','failed','refunded','cancelled') NOT NULL DEFAULT 'pending',
    reference VARCHAR(100) NOT NULL,
    transaction_id VARCHAR(150) NULL,
    gateway_response TEXT NULL,
    admin_notes TEXT NULL,
    payment_proof_path VARCHAR(255) NULL,
    proof_uploaded_at DATETIME NULL,
    paid_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_payments_reference (reference),
    KEY idx_payments_user (user_id),
    KEY idx_payments_course (course_id),
    KEY idx_payments_status (status),
    CONSTRAINT fk_payments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_receipts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id INT UNSIGNED NOT NULL,
    receipt_number VARCHAR(40) NOT NULL,
    pdf_path VARCHAR(255) NULL,
    issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_receipt_number (receipt_number),
    UNIQUE KEY uq_receipt_payment (payment_id),
    CONSTRAINT fk_receipt_payment FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_webhooks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gateway VARCHAR(30) NOT NULL,
    event_type VARCHAR(80) NULL,
    reference VARCHAR(100) NULL,
    payload LONGTEXT NOT NULL,
    signature VARCHAR(255) NULL,
    signature_valid TINYINT(1) NOT NULL DEFAULT 0,
    processed TINYINT(1) NOT NULL DEFAULT 0,
    processed_at DATETIME NULL,
    received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_webhooks_reference (reference),
    KEY idx_webhooks_processed (processed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coupons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    type ENUM('percentage','fixed','full_scholarship') NOT NULL DEFAULT 'percentage',
    value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    course_id INT UNSIGNED NULL COMMENT 'NULL = applies to all courses',
    max_uses INT UNSIGNED NULL COMMENT 'NULL = unlimited',
    used_count INT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATETIME NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_coupons_code (code),
    KEY idx_coupons_active (active),
    CONSTRAINT fk_coupons_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coupon_redemptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    payment_id INT UNSIGNED NOT NULL,
    redeemed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_redemptions_coupon (coupon_id),
    KEY idx_redemptions_user (user_id),
    CONSTRAINT fk_red_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    CONSTRAINT fk_red_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_red_payment FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS refund_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    admin_notes TEXT NULL,
    processed_by INT UNSIGNED NULL,
    processed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_refunds_payment (payment_id),
    KEY idx_refunds_status (status),
    CONSTRAINT fk_refund_payment FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    CONSTRAINT fk_refund_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed example coupons (Phase 10)
INSERT INTO coupons (code, type, value, max_uses, active)
VALUES
    ('WELCOME50', 'percentage', 50.00, 1000, 1),
    ('NSUK2026', 'fixed', 5000.00, 500, 1),
    ('FULLSCHOLAR2026', 'full_scholarship', 100.00, 50, 1)
ON DUPLICATE KEY UPDATE code = VALUES(code);
