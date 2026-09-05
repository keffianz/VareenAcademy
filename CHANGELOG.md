# Changelog — VAREEN Academy LMS

All notable changes to this project are documented in this file.
Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased] — Phase B/D: 10/10 MVP Push

### Added — Payment System (fully wired)
- `src/classes/Payment.php` — orchestration layer: `initialize()`, `verify()` (server-side, idempotent, `FOR UPDATE` race guard), `approveBankTransfer()` / `rejectBankTransfer()`, refund request/processing, receipts, admin queries.
- `src/api/payments.php` — student API: method list, checkout init, verify, coupon validation, payment history (CSRF-enforced).
- `src/api/webhooks.php` — webhook receiver with Paystack `x-paystack-signature` (HMAC-SHA512) and Flutterwave `verif-hash` verification before any state change.
- `src/classes/PaymentGateway.php` (interface), `PaystackGateway.php`, `FlutterwaveGateway.php`, `Coupon.php`.
- Student pages: `checkout.php`, `payments.php` (My Payments), `payment-callback.php`.
- Admin pages: `admin/payments.php` (approve/reject bank transfers, revenue stats, refunds).
- `database/migration_payments.sql` — payments, receipts, webhooks, coupons, redemptions, refund tables.

### Added — Certificates & Legal
- `views/certificate-print.php` — standalone print-optimized certificate document (student-gated).
- `views/student/certificates.php` — student certificate gallery with print links.
- Legal pages: `legal-privacy.php`, `legal-terms.php`, `legal-refund.php` (public routes).

### Added — Security
- `database/migration_lockout.sql` + `User::login()` DB-backed per-account lockout (survives cookie clearing, unlike the previous session-only counter).
- Root + LMS `.htaccess`: web access denied to `storage/`, `uploads/payment_proofs/`, `*.sql`, seed/backup files; reset-token fallback log no longer web-reachable.
- Removed committed production credentials from `api/config.php` (now environment-driven with safe defaults); removed seed/backup files from repo (`admin_seed.php`, `user_seed.php`, `index_backup.php`, `database/demo_passwords.php`).

### Added — Database & Docs
- `database/vareen_full_schema.sql` — consolidated canonical schema (base + payments + certificates + AI + attendance + lockout).
- `Database` class: `update()`, `fetchAll()`, `fetch()`, `insert()`, parameterized `query()`, `getInstance()` singleton — required by the payment layer.
- `docs/` — `ARCHITECTURE.md`, `ERD.md`, `USER_MANUAL.md`, `DEFENSE_PREP.md` (20 Q&A + demo script), `DEPLOYMENT.md`.

### Fixed
- `Payment.php`: repaired 5 truncated methods (`initialize`, `verify`, `rejectBankTransfer`, `processRefund`, `getUserPayments`, `adminPendingBankTransfers`) — file previously had fatal parse errors.
- Router: all new pages registered in the whitelist with correct role gates; `certificates` moved to `views/student/certificates.php`.
- `checkout` uses `c.thumbnail` (correct column) instead of non-existent `c.image`.

## [Unreleased] — Security Hardening Batch 1
## [Unreleased] — Security Hardening Batch 1

### Added
- CSRF protection infrastructure: `csrfToken()` + `requireCsrf()` in `src/middleware/auth.php` (session-stored token, `X-CSRF-Token` header, `hash_equals` comparison, 403 JSON on failure).
- CSRF token exposure for the frontend: `<meta name="csrf-token">` in `views/layout.php`; `window.CSRF_TOKEN` injected on login, signup and password-reset pages.
- Brute-force lockout on login (5 failed attempts → 15-minute lock, session-based).
- Database connection moved to `src/config/database.php` (require + DB_* constants; no more `include`-relative path hacks).
- Admin dashboard: `admin-dashboard` route in `index.php` (admin-gated) + `views/admin/dashboard.php` (platform stats, recent registrations, system status).
- Root `.htaccess` rewrites: `/index.php?page=...` and `/lms_vareen/` now route to the LMS router (fixes all root-absolute sidebar links).
- This changelog.

### Changed
- All state-changing POST endpoints now require a valid CSRF token: `src/api/auth.php` (login, signup, logout, check_email, request_reset, reset_password, change_password) and `src/api/ai_assistant.php` (`ask`).
- `public/js/auth.js`: `getCsrfToken()` helper; `X-CSRF-Token` sent on all 7 calls; logout redirect fixed to `/lms_vareen/index.php?page=login`.
- `public/js/ai-assistant.js`: CSRF header sent with `ask` requests.
- `User::register()`: role whitelisted to `student` — self-registration as teacher/admin is no longer possible.
- `User::login()`: `session_regenerate_id(true)` on success, generic "Invalid email or password" message (no user enumeration), password hash stripped from the returned user record.
- `User::requestPasswordReset()` / `User::resetPasswordWithToken()`: tokens stored as SHA-256 hashes, prior tokens invalidated, tokens never returned to the client, single-use deletion on reset, generic responses (no enumeration). Email via `mail()` with fallback log in `lms_vareen/storage/`.
- `index.php` router: auth gate covers `login|signup|password-reset` (`?page=register` previously fatalled; password-reset was unreachable).
- Signup page: client-side role selector removed; `role: 'student'` sent explicitly.

### Security (fixed vulnerabilities)
- Removed on-screen display of the password-reset token in `views/auth/password-reset.php` (account-takeover vector).
- Removed `console.log` of login credentials in `views/auth/login.php`.
- Fixed open role registration (student-only self-signup enforced server-side and client-side).

### Fixed
- Signup page: malformed `fetch()` call and dangling `roleError` reference (now `signupError`).
- Password-reset page: broken `/src/api/...` fetch paths → `/lms_vareen/src/api/...`.
- Admin login now redirects to the admin dashboard instead of 404-ing.

### Known limitations
- Login lockout is session-based (per browser, not per IP/account) — acceptable for now, revisit in a later batch.
- Admin sub-pages (users, courses, reports, settings) are placeholders pending Phase 8.
