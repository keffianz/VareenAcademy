# VAREEN Academy — Defense Preparation Pack

**Purpose:** the 20 questions a supervisor is most likely to ask, with strong answers, plus a live demo script.

---

## Part 1 — Likely Defense Questions & Answers

### 1. Why plain PHP instead of a framework like Laravel?
Building the LMS on plain PHP (with a hand-written router, middleware, PDO
classes and an interface-based payment layer) demonstrates a deeper
understanding of the fundamentals that frameworks abstract over. The code
follows clear layering, so it can be migrated to a framework later without
re-architecting the domain logic.

### 2. How do you prevent SQL injection?
Every database query goes through PDO **prepared statements** with
`PDO::ATTR_EMULATE_PREPARES => false`, so the database engine performs real
server-side prepares. No user input is ever concatenated into SQL; values are
always bound (`:param`) separately.

### 3. How do you prevent XSS?
All dynamic output is escaped with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
Client-side JavaScript that renders user data uses `textContent` (the verify
page and admin tables build cells via `document.createElement().textContent`),
never `innerHTML` with raw user strings.

### 4. How does your CSRF defense work?
A token is generated once per session (`random_bytes(32)`), delivered via a
`<meta name="csrf-token">` tag, and required on **every** POST via the
`X-CSRF-Token` header. The server compares it with `hash_equals()` and returns
403 JSON on mismatch. It is enforced in every POST-accepting endpoint.

### 5. Why session-based auth rather than JWT?
Sessions are server-side, revocable in an instant (destroy the record), and
simpler to secure in a monolithic app. A session can be terminated by an admin;
a stolen JWT is harder to revoke. We use `session_regenerate_id(true)` on login
to prevent fixation.

### 6. How do you stop students using the AI to cheat on quizzes?
Three layers: (1) the assistant only answers about **enrolled** lessons,
(2) the system prompt forbids giving direct answers to graded work, and (3)
a **fail-closed server-side lock** disables the assistant while a timed quiz is
in progress — if the lock check errors, it returns "locked", never "open".

### 7. Why is the assessment lock fail-closed?
The risk is asymmetric: a false "unlocked" enables cheating; a false "locked"
merely blocks one question. When consequences are asymmetric, you fail safe.

### 8. How are passwords stored? What if the database leaks?
Bcrypt via `password_hash()` — salted and computationally slow by design.
Reset tokens are stored as SHA-256 hashes, are single-use, and expire. A leaked
DB gives an attacker no usable passwords or tokens.

### 9. How is privilege escalation prevented at registration?
`User::register()` whitelists the role to **`student`** on the server. The
client can't choose admin/teacher. Teacher and admin accounts can only be
created through the admin API (role-checked + CSRF).

### 10. How does enrollment protection work?
`enrollments` has a **UNIQUE(student_id, course_id)** constraint, and
`Enrollment::enroll()` runs inside a transaction. This makes double-enrollment
(and the race that causes it) impossible even under concurrency.

### 11. How does the payment system prevent fraud?
Three rules: (1) **server-side verification** — the client callback is never
trusted; the server re-queries the Paystack/Flutterwave API as the only trust
source; (2) the payment is marked `paid` and the student enrolled inside a
**single DB transaction** with a `FOR UPDATE` re-check to defeat double-verify
races; (3) the **amount is sanity-checked** against what was paid before
granting access. Bank transfers bypass the gateway entirely and require
explicit **admin approval**.

### 12. What happens if the student closes the browser mid-payment?
The payment record stays `pending`. Re-opening checkout resumes the same
reference (no duplicate charges). The gateway's webhook can also complete the
payment independently of the browser, because verification is reference-based
and idempotent.

### 13. Why do you verify the webhook signature?
Webhook endpoints are publicly reachable; anyone could POST a fake "payment
succeeded". `webhooks.php` validates Paystack's `x-paystack-signature`
(HMAC-SHA512 of the body with the secret key) before acting. Unsigned or
mismatched payloads are rejected with 403 and logged.

### 14. How are certificates protected against forgery?
Each certificate code is generated with `random_int` over a confusion-free
alphabet (unguessable), issued only by server code when 100% of lessons are
complete, and publicly verifiable at `verify.php` (with QR code). Admins can
revoke a certificate, which immediately invalidates the public check.

### 15. What is your brute-force protection?
`login_attempts` tracks failed logins per email with a lockout window and a
cap (lockout after repeated failures), enforced server-side in `User::login()`
— not in the client — so clearing cookies does not reset it. (See
`migration_lockout.sql`.)

### 16. How does the AI assistant know what lesson the student is on?
The student sends the lesson context from the client, but the server **re-checks

### 17. How would you scale this to 10,000 concurrent users?
Move sessions to Redis, add read replicas for course catalog queries, cache
compiled course pages, queue AI calls, and keep the app stateless apart from
sessions. The schema is already indexed for hot paths (composite unique keys on
`enrollments`, `lesson_progress`; index on `payments.reference`).

### 18. Why did you fragment nothing into microservices?
The workload is a single university-scale LMS — a monolith with clean internal
layering (router → middleware → classes → views) deploys to shared hosting,
needs no service mesh, and is debuggable. Microservices would add operational
cost with no benefit at this scale.

### 19. What are the system's known limitations?
No native mobile app (PWA covers offline), PDF certificates are print-optimized
HTML rather than server-generated PDFs, refunds require a manual bank process,
and AI cost grows linearly with usage (mitigated by per-student daily limits).

### 20. If you had six more months, what would you build first?
(1) Server-side PDF certificate rendering, (2) AI practice-question mode with
auto-grading, (3) teacher analytics dashboards, (4) automated refund processing
through the gateway API, (5) load testing with a seeded 10k-user dataset.

---

## Part 2 — Live Demo Script (8 minutes)

1. **Marketing site** (30s) — homepage, programs, responsive drawer.
2. **Signup → student login** (1 min) — show role-based redirect and CSRF meta
   tag in dev tools.
3. **Enroll in a course → lessons** (2 min) — mark lesson complete, show
   progress updates.
4. **AI assistant** (1 min) — ask about the current lesson; note the daily
   limit message.
5. **Quiz** (1 min) — attempt a quiz; show the AI lock during the attempt.
6. **Payment** (1.5 min) — checkout page → Paystack test card (`4084 0840 8408
   4081`, any future expiry, CVV `408`) → callback shows verified enrollment.
   Mention webhook path for closed browsers.
7. **Certificate** (1 min) — after 100% completion show the certificate, print
   view, then scan the QR / open `verify.php` publicly.
8. **Admin** (30s) — users management, payment approvals, reports.

enrollment and loads the lesson content itself** before building the prompt —
the client-provided context can never grant access to content the student is
not entitled to see.
