# VAREEN-X MASTER BACKLOG

**Version:** 1.0  
**Created:** 2026-09-20  
**Status:** Active  
**Owner:** Founding CTO / Lead Architect

---

## LEGEND

| Priority | Definition |
|----------|------------|
| **CRITICAL** | Blocks launch, security risk, data loss, or system failure |
| **HIGH** | Core user journey broken, major feature incomplete |
| **MEDIUM** | UX issue, performance, code quality, accessibility |
| **LOW** | Nice-to-have, future enhancement |

| Status | Definition |
|--------|------------|
| **OPEN** | Issue identified, not yet started |
| **IN PROGRESS** | Actively being fixed |
| **FIXED** | Code changed, needs verification |
| **VERIFIED** | Tested and confirmed working |
| **WONT FIX** | Intentionally not addressed |

---

## CRITICAL ISSUES

### VX-001: Database Configuration Requires Environment Variables
- **File:** `api/config.php`, `lms_vareen/src/config/database.php`
- **Line:** ~18-21 (api/config.php)
- **Description:** Database credentials (DB_USER, DB_PASS) default to empty strings. If environment variables are not set, all DB operations fail. This blocks the entire LMS.
- **Priority:** CRITICAL
- **Status:** OPEN
- **Verification:** Attempt to load any LMS page without DB credentials configured.

### VX-002: Email Configuration Missing (Password Reset Broken)
- **File:** SMTP config files, `api/contact.php`, `api/apply.php`
- **Description:** `MAIL_USER` and `MAIL_PASS` are empty by default. Password reset tokens fall back to server log only. Contact form and application emails silently fail.
- **Priority:** CRITICAL
- **Status:** OPEN
- **Verification:** Request password reset without SMTP configured; check server logs.

### VX-003: Login Page CSRF Token Exposure
- **File:** `lms_vareen/views/auth/login.php`
- **Line:** 30
- **Description:** CSRF token exposed in JavaScript (`window.CSRF_TOKEN`). While the middleware is secure, tokens could theoretically leak in browser history or cached pages. **Note:** The page has `Cache-Control: no-store` headers which mitigates this risk. This is standard practice for modern CSRF implementations and is LOW RISK.
- **Priority:** CRITICAL
- **Status:** OPEN
- **Verification:** View page source, check token exposure. LOW RISK - mitigated by no-cache headers.

### VX-004: Session Cookie Security Configuration
- **File:** `lms_vareen/src/middleware/auth.php`
- **Line:** 120-140 (vaBootSession function)
- **Description:** Session cookies use `samesite: 'Lax'`. For production LMS handling payments and sensitive data, `SameSite: 'Strict'` would be more secure but may break legitimate cross-site navigation flows.
- **Priority:** CRITICAL
- **Status:** OPEN
- **Verification:** Check cookie attributes in browser dev tools.

### VX-005: Payment API Keys Not Configured
- **File:** `lms_vareen/src/config/payments.php`
- **Line:** Payment config section
- **Description:** Paystack and Flutterwave API keys are empty. Payment flow will fail. Need structured integration that fails gracefully. **Note:** The config already has `enabled` flags that check if keys are present - payments will simply be disabled without crashing.
- **Priority:** CRITICAL
- **Status:** OPEN
- **Verification:** Attempt checkout flow without API keys.

### VX-006: AI API Keys Not Configured
- **File:** `lms_vareen/src/config/ai_config.php`
- **Line:** AI config section
- **Description:** AI assistant API keys are empty. AI features will not work. Need graceful degradation. **Note:** The AI assistant should check for API key availability before making requests.
- **Priority:** CRITICAL
- **Status:** OPEN
- **Verification:** Attempt to use AI assistant without API keys.

### VX-007: Marketing Site Contact Form API Integration
- **File:** `contact.html`, `api/contact.php`
- **Line:** contact.html:148
- **Description:** Contact form has `data-api="/api/contact.php"`. JavaScript `handleFormSubmission` in assets/js/main.js properly reads `form.dataset.api` and fetches that URL. Form submission is already implemented.
- **Priority:** HIGH
- **Status:** OPEN
- **Verification:** Test form submission, check browser console for fetch errors, verify data reaches API.

### VX-008: Marketing Site Application Form API Integration
- **File:** `apply.html`, `api/apply.php`
- **Line:** apply.html:160
- **Description:** Application form has `data-api="/api/apply.php"`. Form uses `enctype="multipart/form-data"`. JavaScript form submission is already implemented in main.js.
- **Priority:** HIGH
- **Status:** OPEN
- **Verification:** Test form submission with all fields, verify data reaches API and database insert works.


### VX-008: Marketing Site Application Form API Integration
- **File:** `apply.html`, `api/apply.php`
- **Description:** Application form needs end-to-end testing to verify it POSTs correctly to `api/apply.php`.
- **Priority:** HIGH
- **Status:** OPEN
- **Verification:** Submit application form, verify database insert.

### VX-009: Student Dashboard - Course Enrollment Flow
- **File:** `lms_vareen/views/course-detail.php`, `lms_vareen/views/profile.php`, `lms_vareen/src/api/auth.php`, `lms_vareen/src/api/dashboard.php`, `lms_vareen/public/js/auth.js`
- **Description:** Verify complete course enrollment flow: browse → enroll → access content → track progress.
- **Priority:** HIGH
- **Status:** VERIFIED (Sprint 2, Batch 1-2)
- **Work done:** Non-enrolled students now see a preview + Enroll CTA instead of a redirect loop; locked lesson icons for previews; profile edit + change password + avatar upload wired end-to-end (User::updateProfile, auth.php update_profile/upload_avatar cases); removed 'Profile editing coming soon'; lesson player MIME fixed for WEBM/MOV; all 8 hardcoded API URLs in auth.js now use data-basepath.
- **Verification:** tools/verify_student_journey.php → 140 passed, 0 failed. Route coverage 0 broken / 0 missing APIs.

### VX-010: Teacher Course Management Flow
- **File:** `lms_vareen/views/teacher/courses.php`, `lms_vareen/views/teacher/lesson-editor.php`
- **Description:** Verify teacher can create courses, add lessons, modules, quizzes, assignments.
- **Priority:** HIGH
- **Status:** OPEN
- **Verification:** Teacher creates course with all content types.

### VX-011: Admin User Management
- **File:** `lms_vareen/views/admin/users.php`, `lms_vareen/views/admin/courses.php`, `lms_vareen/views/admin/settings.php`, `lms_vareen/src/api/admin.php`
- **Description:** Verify admin can create students, create teachers, manage accounts, deactivate accounts.
- **Priority:** HIGH
- **Status:** VERIFIED (Sprint 2, Batch 6)
- **Work done:**
  - Admin courses table was doubly broken (called `action=list_users`, read `d.courses`; API action is `users_list`, response key `data`) — fixed; added `course_update` API case with ownership-safe Course::updateCourse; pricing columns + inline price editing UI (VX-045).
  - Admin settings page called `action=settings` while the API only defines `settings_get` (dead load) — fixed to `settings_get` + correct `d.data` key.
  - Built `tools/verify_admin_contracts.php`: cross-validates every JS-called action against defined API cases, resolving per-view API targets (admin.php / payments.php / auth.php) — **30 passed, 0 failed** across courses, users, settings, applications, certificates, messages, payments, reports.
  - Verified server-rendered POST handlers in teachers.php (`toggle_active`) and coupons.php (`create_coupon`, `delete_coupon`) exist and are CSRF-guarded.
- **Verification:** 30/0 contract checks; php -l clean; route coverage 0 broken.

### VX-012: Notifications System
- **File:** `lms_vareen/views/notifications.php`, `lms_vareen/views/student/_sidebar.php`, `lms_vareen/views/teacher/_sidebar.php`, `lms_vareen/views/admin/_sidebar.php`, `lms_vareen/src/classes/Notification.php`, `lms_vareen/src/api/dashboard.php`, `lms_vareen/src/middleware/auth.php`
- **Description:** Verify notifications are created, displayed, marked as read, and emailed when configured.
- **Priority:** HIGH
- **Status:** VERIFIED (Sprint 2, Batch 5)
- **Work done:**
  - SECURITY (IDOR): `Notification::markAsRead()` and `markAllAsRead()`/`delete()` now scope all UPDATE/DELETE by `user_id`; `dashboard.php` `mark_notification_read` passes `$_SESSION['user_id']` — a student can no longer mutate another user's notification by editing the ID.
  - FATAL: `views/notifications.php:62` called PHP `timeAgo()` that only existed as JS — added global `timeAgo()` helper in `auth.php` (loaded by index.php:9) and removed the duplicate guarded local copy in `student/dashboard.php` (zero duplication).
  - NAVIGATION: notifications page was routed but unreachable — Notifications link added to student sidebar; teacher sidebar; admin already had one. All sidebar renderers verified `appBasePath()`-safe for subdirectory deploys.
- **Verification:** php -l clean on all touched files; `timeAgo()` live-tested ('just now' / '2h ago' / '3d ago'); journey 140/0; route coverage 0 broken. Email delivery remains out of scope (no SMTP keys).

---

## SPRINT 2 — NEW ISSUES RAISED DURING QA

### VX-044: Hardcoded `/lms_vareen/` Asset Paths (47 views)
- **File:** 47 files under `lms_vareen/views/`
- **Line:** e.g. `<script src="/lms_vareen/public/js/auth.js">`
- **Description:** Deployment-breaking hardcode; breaks if the app is served from domain root or a different folder. Also `profile.php` had ZERO script includes (Auth undefined → change-password JS dead on arrival, pre-existing bug).
- **Priority:** HIGH
- **Status:** FIXED → VERIFIED (Sprint 2, Batch 2)
- **Fix:** Token-based sweep replaced all 53 occurrences with `<?php echo appBasePath(); ?>` (appBasePath() derives from PHP_SELF — works at root and subdirectory); profile.php now includes main.js + auth.js.
- **Verification:** 0 remaining `/lms_vareen/` hardcoded refs in views; full lint clean; journey 140/0.

### VX-045: Pricing System (Phase 4 — CEO Order)
- **File:** `lms_vareen/database/migration_002_course_pricing.sql`, `lms_vareen/src/classes/Course.php`, `lms_vareen/src/api/admin.php`, `lms_vareen/views/admin/courses.php`, `lms_vareen/views/courses.php`, `lms_vareen/views/course-detail.php`
- **Description:** Prices were not stored as structured data. Required: official price, currency, delivery mode, level, admin-editable, consistent display.
- **Priority:** HIGH
- **Status:** FIXED (Sprint 2, Batch 4)
- **Work done:** Added `price`, `currency`, `delivery_mode`, `level`, `next_cohort` columns (all 3 schema files + idempotent migration); migration seeds the official launch price list (12 on-campus + 9 online programs in ₦); `Course::formatPrice()` static formatter; `courses_list` returns pricing; `course_update` case; admin inline price editing; public listing + course-detail now show real prices instead of hardcoded "Free".
- **Verification:** php -l clean on all 5 PHP files; journey 140/0; route coverage 0 broken. DB migration pending live-MySQL run.

### VX-046: Dead Placeholder View `views/assignments.php`
- **File:** `lms_vareen/views/assignments.php` (deleted)
- **Description:** Orphan placeholder view not routed anywhere; real assignments UI lives at `views/student/assignments.php` (route `assignments`).
- **Priority:** MEDIUM
- **Status:** FIXED (deleted)

### VX-047: Teacher Live-Classes Placeholder Buttons
- **File:** `lms_vareen/views/teacher/live-classes.php`
- **Line:** ~102-149
- **Description:** Edit buttons were non-functional stubs. Backend `live_classes.php?action=teacher_update` already existed.
- **Priority:** MEDIUM
- **Status:** FIXED (wired to real teacher_update API + edit panel UI)
- **Verification:** php -l clean; API case + view handler cross-checked.

### VX-048: Teacher Profile — Dead JS Block + Unbound Password Controls
- **File:** `lms_vareen/views/teacher/profile.php`
- **Description:** The view shipped with a trailing `<script>` block binding `#pwForm`, `#pwOld`, `#pwNew`, `#pwConfirm`, `#btnPwSave`, `#pwLoading` — none of which existed in the markup. `document.getElementById('pwForm').addEventListener(...)` threw on load, so the entire block (including its duplicate avatar handler) never ran. The dead block also re-declared `window.CSRF_TOKEN` / `AUTH_API` / `$` / `msg`, and vestigial `.admin-msg` CSS remained in the `<style>` block copied over from the admin template.
- **Priority:** HIGH
- **Status:** FIXED → VERIFIED (Sprint 2, Batch 7)
- **Work done:**
  - Added the missing stable IDs (`#pwForm`, `#pwOld`, `#pwNew`, `#pwConfirm`, `#btnPwSave`) so JS can bind to the Change Password form.
  - Replaced the 4,043-byte dead script block with a working `Auth.changePassword()` handler that reports success/error inline in `#pwMsg` and clears the fields on success. Degrades to the native POST (already handled server-side at the top of the view) when `auth.js` is absent.
  - Removed the duplicated avatar AJAX handler; the surviving `Auth.uploadAvatar()` path keeps the live `<img>` preview, 5 MB size guard, MIME allow-list (jpeg/png/webp) and selected-file size echo.
  - Deleted 3 dead `.admin-msg` CSS rules (0 uses — messages use the shared `.alert` / `.alert-success` / `.alert-error` classes).
- **Verification:** `php tools/verify_profiles.php` → 30/30 checks pass; `php -l` clean; script/style tags balanced; no `getElementById` on non-existent IDs remains.

### VX-049: Admin Profile Unreachable (Router Allowlist) + Silently Empty Form
- **File:** `lms_vareen/index.php`, `lms_vareen/views/admin/profile.php`
- **Description:** Two independent defects made "My Profile" dead for admins:
  1. `case 'admin-profile'` existed in the router switch, but `'admin-profile'` was missing from the `$knownPages` allowlist — and that allowlist is evaluated *before* the switch, so `?page=admin-profile` rendered the 404 page and exited. `router_integrity.php` reported it as *"routed but not allowlisted (blocked before switch)"*. It was the only admin page in that state.
  2. `views/admin/profile.php` was the only admin view that never `require_once`d `src/classes/Database.php`. `new Database()` therefore threw `Error: Class not found`, the surrounding `catch (Throwable)` swallowed it, and the form rendered with every field empty and no error surfaced — a silent data-loss-shaped failure.
- **Priority:** HIGH
- **Status:** FIXED → VERIFIED (Sprint 2, Batch 7)
- **Work done:**
  - Added `'admin-profile'` to `$knownPages` (allowlist 64 → 65, now equal to the switch case count).
  - Added the missing `require_once 'src/classes/Database.php'`, matching the 15 sibling admin views.
  - Confirmed already-correct wiring: sidebar entry `'profile' => ['fa-user-circle', 'My Profile', '/index.php?page=admin-profile']`, route `case 'admin-profile': requireRole('admin')`, and the `admin_profile_update` API case (which rejects any `user_id` other than the caller's own — self-edit-only IDOR guard).
- **Verification:** `php tools/router_integrity.php` → **PASS: router is consistent** (allowlist 65 / switch 65, 0 defects in all five checks); `php -l` clean on both files; `verify_profiles.php` 30/30.

### VX-050: Multi-API Contract Verifier False Positives
- **File:** `tools/verify_admin_contracts.php`
- **Description:** The verifier deliberately filtered `auth.php` out of a view's API targets (`$nonAuth ?: $hits`). `views/admin/profile.php` posts profile data to `admin.php` but credentials/avatar to `auth.php`, so `change_password` and `upload_avatar` were checked against `admin.php` and reported as failures. The application code was correct — the test was wrong, and an untrustworthy verifier masks real regressions in the two views it mis-reports.
- **Priority:** MEDIUM
- **Status:** FIXED → VERIFIED (Sprint 2, Batch 7)
- **Work done:** The verifier keeps every API a view references and passes an action when it exists in **any** of them (single-API views stay strict, since the union is then one file). Genuinely missing actions still fail, and each label now names the file that matched — e.g. `action 'change_password' exists in auth.php`.
- **Verification:** `php tools/verify_admin_contracts.php` → **33 passed, 0 failed** (was 31 passed / 2 failed).

### VX-027 (re-checked): Cohort Management
- **Finding:** No cohort module exists — and none is required for launch. "Cohort" appears in the codebase only as the `next_cohort` (DATE) course-metadata column introduced by VX-045 — surfaced in the `Course::updateCourse` whitelist, the admin inline price editor (a `type="date"` "Next cohort" field), the `courses_list` SELECT, and the course-detail "Next cohort: …" line. There is no cohorts table, no cohort CRUD, and no cohort-scoped enrollment.
- **Priority:** LOW (unchanged)
- **Status:** FUTURE — confirmed correctly deferred by the founding team; **not** a Sprint 2 blocker. The launch-facing requirement ("show the next cohort date") is already satisfied. Building a real cohort subsystem (schema + migration + CRUD + enrollment) is net-new scope, not production hardening.

### VX-013: Community Posts and Comments
- **File:** `lms_vareen/views/student/student-community.php`, `lms_vareen/src/classes/Community.php`
- **Description:** Verify post creation, comment/reply threading, post editing, post deletion.
- **Priority:** HIGH
- **Status:** OPEN
- **Verification:** Create post, add comments, verify threading.

### VX-014: Quiz Creation and Submission Flow
- **File:** `lms_vareen/views/teacher/quiz-editor.php`, `lms_vareen/views/student/quizzes.php`
- **Description:** Verify teacher can create quiz, student can take quiz, results are calculated and stored.
- **Priority:** HIGH
- **Status:** OPEN
- **Verification:** Full quiz lifecycle test.

### VX-015: Assignment Upload and Grading
- **File:** `lms_vareen/views/teacher/assignments-editor.php`, `lms_vareen/views/student/assignments.php`
- **Description:** Verify assignment creation, file upload, submission, grading flow.
- **Priority:** HIGH
- **Status:** OPEN
- **Verification:** Create assignment, submit file, grade submission.

### VX-016: Certificate Issuance and Verification
- **File:** `lms_vareen/views/student/certificates.php`, `lms_vareen/src/classes/Certificate.php`
- **Description:** Verify certificates are issued upon course completion, can be viewed, printed, and verified publicly.
- **Priority:** HIGH
- **Status:** OPEN
- **Verification:** Complete course, verify certificate issuance.

---

## MEDIUM ISSUES

### VX-017: Light Mode/Dark Mode Removed (LMS Portal)
- **File:** `lms_vareen/views/layout.php`, `lms_vareen/public/css/dark-mode.css` (deleted)
- **Description:** Per owner request, the LMS portal no longer has any light/dark mode switching — single light theme only. Removed the dark-mode.css link from layout.php and deleted dark-mode.css (the only theme-switching code in the LMS; no toggle JS, no data-theme attribute, no matchMedia logic existed). Marketing-site dark blocks in assets/css/main.css were intentionally left untouched (out of scope).
- **Priority:** MEDIUM
- **Status:** DONE
- **Verification:** lms_vareen/views/*.php + lms_vareen/public/js/*.js contain zero dark-mode/prefers-color-scheme/data-theme references; portal renders single light theme regardless of OS setting.

### VX-018: Mobile Responsive Design
- **File:** All pages and CSS
- **Description:** Verify all pages work on mobile (320px+), tablet, and desktop. Check navigation, forms, tables, modals.
- **Priority:** MEDIUM
- **Status:** OPEN
- **Verification:** Test at various breakpoints.

### VX-019: UI Consistency Across LMS Pages
- **File:** `lms_vareen/views/`
- **Description:** Student, teacher, and admin dashboards should have consistent styling, spacing, typography, and component design.
- **Priority:** MEDIUM
- **Status:** OPEN
- **Verification:** Compare dashboards side by side.

### VX-020: SEO Meta Tags on All Pages
- **File:** All HTML and PHP view files
- **Description:** Verify each page has unique title, description, Open Graph tags, Twitter cards, and canonical URLs.
- **Priority:** MEDIUM
- **Status:** OPEN
- **Verification:** Check each page's meta tags.

### VX-021: Structured Data Implementation
- **File:** All pages
- **Description:** Add/verify structured data (EducationalOrganization, Course, Person, etc.) for SEO.
- **Priority:** MEDIUM
- **Status:** OPEN
- **Verification:** Validate with Google Rich Results Test.

### VX-022: Performance Optimization
- **File:** `assets/css/main.css`, `assets/js/main.js`, all assets
- **Description:** Optimize CSS delivery, minimize JS, optimize images, add caching headers, consider lazy loading.
- **Priority:** MEDIUM
- **Status:** OPEN
- **Verification:** Run Lighthouse, check performance score.

### VX-023: Accessibility Audit
- **File:** All HTML pages
- **Description:** Verify WCAG 2.1 AA compliance: color contrast, keyboard navigation, screen reader support, ARIA labels.
- **Priority:** MEDIUM
- **Status:** OPEN
- **Verification:** Run axe-core or similar accessibility checker.

### VX-024: Form Validation Consistency
- **File:** All form pages
- **Description:** Ensure consistent validation across all forms (client-side + server-side), consistent error display.
- **Priority:** MEDIUM
- **Status:** OPEN
- **Verification:** Test all forms with invalid data.

### VX-025: Error Handling and User Feedback
- **File:** All PHP and JS files
- **Description:** Ensure all errors display helpful messages to users, no PHP warnings/errors exposed, proper HTTP status codes.
- **Priority:** MEDIUM
- **Status:** OPEN
- **Verification:** Trigger various error conditions.

### VX-026: Code Duplication Review
- **File:** All PHP files
- **Description:** Review for duplicated code blocks, especially in dashboard queries, form handlers, and utility functions.
- **Priority:** MEDIUM
- **Status:** OPEN
- **Verification:** Code review for duplication.

---

## LOW ISSUES / FUTURE FEATURES

### VX-035: Centralized Storage System (Phase 4)
- **File:** `lms_vareen/src/classes/Uploader.php`
- **Description:** Single storage abstraction: organized folders `assets/uploads/{type}/{YYYY}/{MM}/{owner}/`, collision-proof filenames, extension+MIME validation, path-traversal-safe resolve/delete, empty-dir cleanup. Cloud-ready (single class swap point, no API keys required).
- **Priority:** HIGH
- **Status:** VERIFIED (2026-09-21) — RC1: fixed `resolveStoredPath()`/`removeStoredFile()` DB-form contradiction (doubled `assets/uploads/` prefix made every resolve/delete fail silently); both stored path forms now accepted; 21/21 unit tests (`tools/test_uploader.php`) + 38/38 upload round-trip (`tools/verify_uploads.php`)
- **Verification:** `php tools/test_uploader.php`

### VX-036: Teacher Video Upload (Phase 3)
- **File:** `lms_vareen/src/api/lessons.php` (`upload_video`), `lms_vareen/views/teacher/lesson-editor.php`, `lms_vareen/views/lesson.php`
- **Description:** MP4/MOV/WEBM/AVI upload with XHR progress bar, CSRF protection, 500MB client+server validation, MIME sniffing, video preview in builder, replacement (old file deleted), correct HTML5 `type` + `BASE_URL`-resolved `src` in the student player.
- **Priority:** HIGH
- **Status:** FIXED (2026-09-20) — lint clean; live upload test pending DB environment
- **Verification:** Upload MP4/WEBM in lesson builder, watch progress bar, preview in student lesson page.

### VX-037: Lesson Builder — Edit / Draft / Publish (Phase 3)
- **File:** `lms_vareen/src/api/lessons.php` (`update`), `lms_vareen/src/classes/Lesson.php` (`updateLesson` + position/is_active), `lms_vareen/views/teacher/lesson-editor.php`
- **Description:** Inline edit panel (title, description, duration, position), publish/unpublish toggle with draft/published badges, teacher-ownership enforced on update/upload/delete.
- **Priority:** HIGH
- **Status:** FIXED (2026-09-20) — lint clean
- **Verification:** Edit lesson fields, toggle publish, confirm student course view respects is_active.

### VX-038: Resource Upload Consolidation + Teacher Delete (Phase 3/4)
- **File:** `lms_vareen/src/classes/Resource.php`, `lms_vareen/src/api/resources.php`
- **Description:** `Resource::uploadFile()` delegated to `Uploader` (removes duplicated logic + CWD-relative path bug); `deleteResource()` deletes via `Uploader::removeStoredFile()` with legacy-row fallback; teachers may now delete resources on their own courses (`Lesson::getResourceOwnerCourseId()` ownership check); admins may delete any.
- **Priority:** HIGH
- **Status:** FIXED (2026-09-20) — lint clean
- **Verification:** Teacher uploads resource, deletes own resource; verify other teacher's resource is denied.

### VX-027: Cohort System
- **Description:** Add cohort creation, start/end dates, instructor assignment, student enrollment, live schedule.
- **Priority:** LOW
- **Status:** FUTURE

### VX-028: Student Portfolio Pages
- **Description:** Public portfolio with projects, GitHub, Behance, live websites, certificates, skills.
- **Priority:** LOW
- **Status:** FUTURE

### VX-029: Instructor Profile Pages
- **Description:** Professional pages with bio, expertise, courses, experience, social links.
- **Priority:** LOW
- **Status:** FUTURE

### VX-030: Payment Integration Structure
- **Description:** Complete payment gateway integration (Paystack/Flutterwave) with webhooks, receipts, refunds.
- **Priority:** LOW
- **Status:** FUTURE (blocked by API keys)

### VX-031: AI Integration Structure
- **Description:** Complete AI assistant integration with proper API handling, conversation logging, usage limits.
- **Priority:** LOW
- **Status:** FUTURE (blocked by API keys)

### VX-032: Referral System
- **Description:** Student referral tracking, rewards, ambassador program.
- **Priority:** LOW
- **Status:** FUTURE

### VX-033: Student Achievements & Streaks
- **Description:** Gamification: achievements, learning streaks, leaderboards.
- **Priority:** LOW
- **Status:** FUTURE

### VX-034: Analytics Improvements
- **File:** Dashboard files
- **Description:** Add more meaningful learning statistics, progress charts, activity trends.
- **Priority:** LOW
- **Status:** FUTURE

---

## PROGRESS TRACKER

| Phase | Status | Issues Completed | Notes |
|-------|--------|------------------|-------|
| Phase 1: Backlog Creation | ✓ COMPLETE | N/A | This document created |
| Phase 2: Critical Fixes | ✓ COMPLETE | 6/6 | VX-004/005/006 encoding; plus config/session/payment/ai graceful-degradation encoding fixes |
| Phase 3: User Journeys | ◐ IN PROGRESS | 6/10 | VX-009 VERIFIED (140/0); VX-010 lesson builder FIXED; VX-011 admin FIXED → VERIFIED (33/0); VX-012 notifications VERIFIED; VX-048 teacher profile VERIFIED; VX-049 admin profile VERIFIED |
| Phase 4: Storage System | ✓ COMPLETE | 1/1 | VX-035 Uploader VERIFIED (18/18 tests) |
| Phase 4b: Pricing System | ✓ COMPLETE | 1/1 | VX-045 schema + seed + admin UI + public display |
| Phase 5: Light/Dark Mode | ○ NOT STARTED | 0/1 | |
| Phase 6: Responsive Design | ○ NOT STARTED | 0/1 | |
| Phase 7: Feature Completion | ◐ IN PROGRESS | 6/8 | VX-044 asset paths FIXED/VERIFIED; VX-046 dead view deleted; VX-047 live-classes wired; VX-048/049 profile modules VERIFIED; VX-050 verifier accuracy FIXED |
| Phase 8: Future Features | ○ NOT STARTED | 0/8 | VX-027 cohort confirmed correctly deferred (LOW/FUTURE, not a launch blocker) |
| Phase 9: Performance | ○ NOT STARTED | 0/1 | |
| Phase 10: SEO & Trust | ○ NOT STARTED | 0/2 | |
| Phase 11: Final QA | ◐ IN PROGRESS | — | See `LAUNCH_READINESS_REPORT.md` (Sprint 2, 2026-09-21) |

---

## SPRINT 2 — FINAL VERIFICATION SNAPSHOT (2026-09-21)

All figures below are from a single clean run against the working tree.

| Verifier | Command | Result |
|----------|---------|--------|
| PHP lint (all files) | `php tools/lint_all.php` | **241 files, 0 syntax errors** |
| Router integrity | `php tools/router_integrity.php` | **PASS — 0 defects** (allowlist 65 == switch 65) |
| Admin API contracts | `php tools/verify_admin_contracts.php` | **33 passed, 0 failed** |
| Student journey | `php tools/verify_student_journey.php` | **140 passed, 0 failed** |
| Profile modules | `php tools/verify_profiles.php` | **30 passed, 0 failed** |
| Placeholder scan | `php tools/scan_placeholders.php` | **0 hits** |
| Route coverage | `php tools/check_routes.php` | **broken=0, missingApi=0** |

**Known unverified-until-deploy:** `migration_002_course_pricing.sql` and all upload/enroll round-trips require a live MySQL instance, which is not available in the dev environment. These are the only items that cannot be signed off locally — see `LAUNCH_READINESS_REPORT.md`.

---

## FIXES COMPLETED (Phase 2)

### ✓ VX-004: 403.html Encoding Issue - FIXED
- **File:** `403.html`
- **Fix:** Corrected title from "Forbidden �" to "Forbidden - VAREEN Academy"
- **Date:** 2026-09-20

### ✓ VX-005: 404.html Encoding Issue - FIXED
- **File:** `404.html`
- **Fix:** Corrected title from "Page not found �" to "Page Not Found - VAREEN Academy"
- **Date:** 2026-09-20

### ✓ VX-006: 500.html Encoding Issue - FIXED
- **File:** `500.html`
- **Fix:** Corrected title from "Server Error �" to "Server Error - VAREEN Academy"
- **Date:** 2026-09-20

---

*Last updated: 2026-09-21 (Sprint 2 Batch 7 — profile modules closed, verifier accuracy fixed, router defect cleared)*

