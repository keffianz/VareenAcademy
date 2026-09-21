# VAREEN Academy — RC1 Deployment Report

**Date:** 2026-09-21 · **Branch:** master · **Gate:** all verifiers green

## Verdict: **READY FOR RC1 DEPLOYMENT**

## 1. Final Gate Results

| # | Verifier | Scope | Result |
|---|----------|-------|--------|
| 1 | `php tools/test_uploader.php` | Uploader unit suite (resolve forms, traversal, delete) | **21/21 PASS** |
| 2 | `php tools/verify_uploads.php` | Writable dirs, .htaccess guards, .user.ini limits, upload round-trip | **38/38 PASS** |
| 3 | `php tools/verify_all.php` | Lint + router integrity + admin contracts + student journey + profiles + placeholders | **6/6 PASS** (lint: 247 files / 0 errors; journey: 140/0; placeholders: 0) |
| 4 | `php tools/verify_production_config.php` | Debug leftovers, localhost URLs, hardcoded creds across deployables | **0 problems / 149 files** |

## 2. Critical Defect Fixed This Sprint — `Uploader` path contradiction (VX-035 regression)

**Symptom:** `resolveStoredPath()` and `removeStoredFile()` returned `false` for **every**
valid file. `upload()` stores DB paths as `assets/uploads/{type}/{Y}/{M}/…` while the two
methods prepended `absoluteBase` (which already *is* `assets/uploads`) to that string and
probed a **doubled** path `assets/uploads/assets/uploads/…` — `realpath()` always failed.
The two checks used contradictory path forms, so downloads/avatars/resources that relied on
the seam silently fell back, and `Resource::deleteResource()` never deleted files.

**Why tests looked green:** the old `test_uploader.php` wrote its probe *into the doubled
path* to match the bug, masking the defect (18/18 "pass" while production was broken).

**Fix (`lms_vareen/src/classes/Uploader.php`):**
- New private `toBaseRelative()` normalizer strips the `assets/uploads/` prefix when a
  DB-relative path is passed, rejects drive letters / absolute paths / `..` traversal.
- `resolveStoredPath()` and `removeStoredFile()` now accept **both** stored forms
  (DB-relative and base-relative), keep the base containment check, and preserve the
  type-dir check (cross-type resolves rejected even when the file exists).
- Tests corrected to probe the real single-level storage location; added DB-form,
  base-relative-form and cross-type contract checks. `.gitkeep` in type dirs means the
  empty-dir cleanup now correctly **stops at the type dir** (required dirs never deleted).

## 3. Deploy Checklist (Hostinger)

1. Upload repo contents; point document root at `lms_vareen/` (or public alias to it).
2. Create MySQL DB + user; import `sql/setup_shared_hosting.sql` (or `lms_vareen/database/schema.sql` + seeds).
3. Configure `lms_vareen/src/config/database.php` credentials (no secrets committed — verify passes).
4. Ensure PHP 8.0+; `.user.ini` (500M upload limits) is read by LiteSpeed per-directory.
5. Confirm writable dirs keep their `.htaccess` guards: `assets/uploads/`, `uploads/payment_proofs/`, `storage/`.
6. Set production payment gateway keys (Paystack/Flutterwave) in the admin panel/env — not in repo.
7. Post-deploy smoke: run `php tools/verify_all.php` and `php tools/verify_uploads.php` on the server.

## 4. Remaining Blockers (none blocking file deploy)

- **Live DB migration:** schema import must run against the production MySQL instance (script ready, needs credentials).
- **SMTP/email:** outbound mail unconfigured until host SMTP creds are set.
- **Gateway keys:** production Paystack/Flutterwave keys must be entered post-deploy.

## 5. Sprint Summary

- Fixed RC1 blocker: Uploader path contradiction (+ corrected the test suite that masked it).
- Closed VX-027/035/038/048/049/050; repo hygiene sweep (402 junk files removed).
- Dark-mode stylesheet wired into `layout.php`; `robots.txt`/`sitemap.xml` updated;
  writable dirs created with `.htaccess` guards; `.user.ini` sets 500M upload limits.
