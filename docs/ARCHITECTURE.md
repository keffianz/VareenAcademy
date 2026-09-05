# VAREEN Academy — System Architecture

**Version:** v1.0 · **Status:** Final-Year Project (B.Sc. Computer Science, NSUK)

## 1. High-Level Overview

VAREEN Academy is a **two-part monorepo**:

| Part | Tech | Purpose |
|---|---|---|
| Marketing website (repo root) | Static HTML + PHP form APIs | Public pages, applications, contact, exam registrations |
| LMS (`lms_vareen/`) | Pure PHP 8 + MySQL (PDO) | The actual learning platform |

The LMS is **server-rendered, session-based, no framework**. Views are plain PHP
templates wrapped by a shared layout; the router is a whitelist of `?page=` values.

## 2. Request Lifecycle

```
Browser
  │  ?page=student-dashboard
  ▼
lms_vareen/index.php            ← single entry-point router
  │
  ├─ session_start()
  ├─ require src/middleware/auth.php   (helpers: requireLogin, requireRole,
  │                                     csrfToken, requireCsrf, appBasePath)
  ├─ public pages? (login/signup/password-reset/verify/instructors/
  │                 become-instructor/legal-*) → render directly, exit
  ├─ whitelist check (knownPages) → 404 if unknown (even for guests)
  ├─ requireLogin()
  │
  ├─ switch($page) → requireRole() → render_page('views/...', title)
  │                     │
  │                     ├─ view includes src/classes/*.php (User, Course,
  │                     │   Enrollment, Lesson, Quiz, Payment, Certificate…)
  │                     └─ classes talk to MySQL via PDO (src/classes/Database.php)
  │
  └─ views/layout.php          ← captures $view_content, adds <head>, assets

JSON APIs:  lms_vareen/src/api/*.php  (auth, lessons, quizzes, admin,
            payments, webhooks, ai_assistant, live_classes, …)
            → checkAuth() → requireCsrf() on every POST → action switch → JSON
```

## 3. Layering

```
┌─────────────────────────────────────────────────────────┐
│ VIEWS (views/)            — HTML + minimal inline JS     │
├─────────────────────────────────────────────────────────┤
│ API (src/api/)            — JSON endpoints, role-gated   │
├─────────────────────────────────────────────────────────┤
│ DOMAIN (src/classes/)     — User, Course, Module,        │
│                             Lesson, Enrollment, Quiz,    │
│                             Certificate, Payment,        │
│                             Coupon, Notification,        │
│                             AIAssistant                  │
├─────────────────────────────────────────────────────────┤
│ DATA (Database.php + schema) — PDO, prepared statements  │
└─────────────────────────────────────────────────────────┘
```

- Views never talk to the DB directly except small read-only listing queries.
- All state changes go through domain classes or role-checked API endpoints.
- Every class uses **PDO prepared statements** (`ATTR_EMULATE_PREPARES=false`).
## 4. Authentication & Authorization

- **Sessions** with `session_regenerate_id(true)` on login (anti-fixation).
- **Passwords**: bcrypt via `password_hash()`.
- **Roles** stored server-side; `requireRole()` re-checks on every routed page and every API action.
- **Registration** whitelists role to `student`; teachers/admins are created only by admins.
- **Brute-force lockout**: session-based counters *plus* DB-backed
  `failed_login_attempts` / `locked_until` that survive cookie clearing.

## 5. CSRF

- One token per session (`random_bytes(32)`), exposed via `<meta name="csrf-token">`.
- Every POST (`requireCsrf()`) compares the `X-CSRF-Token` header with `hash_equals`.
- Enforced in **all** POST-accepting endpoints: auth, admin, lessons, quizzes,
  payments, ai_assistant, live_classes, assignments, resources, public forms.

## 6. Security Headers (lms_vareen/.htaccess)

`X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`,
`X-XSS-Protection`, `Referrer-Policy`, plus a 403 rewrite for
`src/`, `assets/uploads/`, `database/`, `storage/`, `uploads/` and `.log` files.

## 7. Payment Flow

```
Checkout (views/checkout.php)
  └─ src/api/payments.php?action=initialize  (CSRF, student-only)
       └─ Payment::initialize()
            ├─ validates course, coupon, method
            ├─ inserts payments row (status=pending)
            ├─ Paystack/Flutterwave → returns gateway redirect URL
            └─ bank_transfer       → returns reference + upload-proof flow
Student pays at gateway ──► callback: ?page=payment-callback&reference=…
  └─ src/api/payments.php?action=verify
       └─ Payment::verify() — SERVER-side gateway verification (only trust)
            ├─ amount sanity check (±5%)
            └─ completePayment(): transaction + FOR UPDATE guard
                 → enroll + receipt + coupon usage + notification
Webhook (real-time path)
  └─ src/api/webhooks.php — signature verified (HMAC-SHA512 Paystack /
                            verif-hash Flutterwave) → logged → verify()
Admin bank transfer
  └─ views/admin/payments.php → admin_approve / admin_reject
       └─ Payment::approveBankTransfer() → completePayment()
Refunds
  student request → Payment::requestRefund() → admin processRefund()
```

**Design principle:** the gateway API is the *only* trust source. Client-side
callbacks are never trusted; every success is confirmed server-side.

## 8. Certificate Flow

```
Lesson marked complete → LessonProgress::markComplete()
  └─ Certificate::issueIfEligible()
       ├─ active enrollment? + all active lessons complete? (else nothing)
       ├─ already issued? → idempotent return
       └─ INSERT certificates with code VER-XXXXXX-XXXXXX
Student: views/student/certificates.php (list + PDF + verify)
  └─ certificate-print.php  → A4 landscape, window.print() → PDF
Public:  views/verify.php   → code lookup, status + QR
Admin:   views/admin/certificates.php → revoke / restore / issue-by-email
```

## 9. AI Assistant

- Student-only, lesson-scoped, **enrollment-checked**.
- Daily usage limit (DB-counted) and conversation logging.
- **Assessment lock**: during any in-progress timed quiz the assistant is
  disabled server-side (fail-closed: any error → locked).
- Anti-cheat system prompt forbids direct answers to graded work.
- Config: `src/config/ai_config.php` + `src/classes/AIAssistant.php`.
## 10. Database

Single canonical schema: **`lms_vareen/database/vareen_full_schema.sql`**.
Consolidates the base schema plus all migrations (batch2, ai_conversations,
quiz_evaluation, payments, lockout). See `docs/ERD.md` for the diagram and
`docs/DEPLOYMENT.md` for applying it.

Key design points:
- InnoDB + utf8mb4, foreign keys with sane delete rules.
- `users.UNIQUE(email, role)` enables one account per role per email for demo
  accounts, while `User::register()` keeps normal signups globally unique.
- Unique DB constraints prevent double-enrollment/progress races.
- `courses.teacher_id` intentionally **not** CASCADE (protects course data).

## 11. Asset & Code Layout (LMS)

```
lms_vareen/
├── index.php                  router
├── .htaccess                  security headers + rewrite + cache
├── src/
│   ├── api/                   JSON endpoints (incl. webhooks.php)
│   ├── classes/               domain classes
│   ├── config/                database.php, payments.php, ai_config.php
│   └── middleware/            auth.php
├── views/                     page templates (+ student/, teacher/, admin/)
├── public/                    css/, js/ (incl. ai-assistant)
├── uploads/                   user content (blocked from direct HTTP)
├── storage/                   runtime logs (blocked from direct HTTP)
└── database/                  schema + migrations + vareen_full_schema.sql
```

## 12. Dependencies

- PHP ≥ 8.0 (`match`, arrow functions), extensions: pdo_mysql, curl, fileinfo.
- MySQL / MariaDB 5.7+.
- Front-end: FontAwesome (CDN), vanilla JS, no build step.
- No composer packages; the LMS is self-contained PHP.

## 13. Known Limitations (honest notes)

- Email SMTP is configured but `MAIL_USER/MAIL_PASS` are empty by default —
  password-reset tokens fall back to a server log when mail is unavailable.
- Live classes use manual meeting URLs; Zoom/Meet API integration is future work.
- PWA/service-worker exists on the marketing site; the LMS itself is not a PWA.
- Test coverage is an HTTP smoke suite (39 checks) plus `php -l`; there are no
  PHPUnit unit tests yet.

## 14. Scale & Future

- Schema is index-covered for hot queries; sessions could move to Redis and
  catalogs to a cache without changing domain code.
- The payment gateway pair (Paystack + Flutterwave behind one interface) is
  designed so adding a third gateway is a single small class.
- The whitelist router + middleware are structured to migrate to a framework
  (e.g. Laravel) without re-architecting the domain logic.