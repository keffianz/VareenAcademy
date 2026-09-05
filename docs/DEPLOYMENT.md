# VAREEN Academy — Deployment Guide

**Target:** Hostinger shared hosting (single domain, root = marketing site, `/lms_vareen` = LMS).

---

## 1. Prerequisites

| Requirement | Value |
|---|---|
| PHP | 8.0+ (8.1+ recommended) with `pdo_mysql`, `curl`, `mbstring`, `openssl` |
| MySQL | 5.7+ / MariaDB 10.3+ |
| HTTPS | Active SSL certificate (free AutoSSL works) |
| Mail | SMTP account for password resets (optional — resets fall back safely) |

## 2. Database Setup

Run **in this order** from phpMyAdmin:

1. `lms_vareen/database/vareen_full_schema.sql` — canonical full schema
   (base tables + payments + certificates + AI + instructor applications +
   attendance + lockout, consolidated).
2. Optionally `database/seed_demo.php` via CLI for demo data — **never in
   production**, and delete the file after use.

## 3. Configuration (environment variables)

Set these in Hostinger → *Advanced → PHP Configuration* or a `.env`-style
include **outside the document root**. Never hard-code secrets.

| Variable | Used by | Notes |
|---|---|---|
| `DB_HOST` | LMS + marketing APIs | usually `localhost` |
| `DB_NAME` | LMS | e.g. `u374397808_vereenacademy` |
| `DB_USER` / `DB_PASS` | LMS + marketing APIs | **rotate before launch** |
| `PAYSTACK_SECRET_KEY` | `src/config/payments.php` | `sk_test_…` then `sk_live_…` |
| `PAYSTACK_PUBLIC_KEY` | checkout view | `pk_…` |
| `FLUTTERWAVE_SECRET_KEY` / `FLW_WEBHOOK_SECRET` | payments + webhooks | secret hash for signature verification |
| `ANTHROPIC_API_KEY` | `src/config/ai_config.php` | enables the AI assistant |
| `MAIL_USER` / `MAIL_PASS` | password reset mail | optional fallback exists |

Notes:
- `src/config/local_db.php` (git-ignored) overrides credentials **locally
  only**; on the server use environment variables.
- AI assistant auto-disables when `ANTHROPIC_API_KEY` is absent.

## 4. File Permissions

| Path | Permission | Purpose |
|---|---|---|
| `lms_vareen/uploads/` | 755 | user uploads |
| `lms_vareen/uploads/payment_proofs/` | 755 | bank-transfer proofs (blocked from web) |
| `lms_vareen/storage/` | 700 | logs/reset-token fallback (blocked from web) |

`.htaccess` already denies web access to `src/`, `database/`, `storage/`,
`uploads/payment_proofs/`, `*.sql`, and `api/config.php` — keep those rules.

## 5. Webhooks (one-time, per gateway)

| Gateway | URL | Events |
|---|---|---|
| Paystack | `https://<domain>/lms_vareen/src/api/webhooks.php?gateway=paystack` | `charge.success` |
| Flutterwave | `https://<domain>/lms_vareen/src/api/webhooks.php?gateway=flutterwave` | `charge.completed` |

The endpoint verifies each provider's signature header before processing.

## 6. Post-deploy Checklist

- [ ] Login as admin → change the default admin password immediately.
- [ ] Settings → verify support email, phone, bank details.
- [ ] Delete any seed scripts (`seed_demo.php`, `admin_seed.php`) from server.
- [ ] Submit a real test payment (Paystack test key) end-to-end.
- [ ] Trigger a password reset; confirm no token appears in `storage/` logs
      (mail configured) and `storage/password_resets.log` is **not** reachable
      via URL (expect 403).
- [ ] Visit `/lms_vareen/index.php?page=verify` and verify a real certificate.
- [ ] Check `php -l` clean on all edited files (done before release).

## 7. Rollback

Keep the previous release as `lms_vareen_backup_<date>/`. The schema
migrations are additive; restoring code + previous DB dump is sufficient.
