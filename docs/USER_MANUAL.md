# VAREEN Academy — User Manual

**Audience:** students, teachers, administrators

---

## A. Getting Started

### Create an account
1. Go to the LMS: `https://vereenacademy.com/lms_vareen/`
2. Click **Sign Up**.
3. Enter your details and a strong password (min 8 characters).
4. You now have a **Student** account.

### Log in by role
Use the role you intend to use. Each role has its own dashboard:
- **Students** → student dashboard with enrolled courses
- **Teachers** → teacher dashboard with editors
- **Admins** → admin panel

---

## B. Student Guide

### Browse and enroll in courses
1. **Browse Courses** from the sidebar.
2. Free courses: click **Enroll Now (Free)** — you're in instantly.
3. Paid courses: click **Enroll — ₦X** → checkout:
   - Choose **Paystack / Flutterwave** for instant card/bank/USSD payment, OR
   - Choose **Direct Bank Transfer** → transfer the exact amount using your
     reference as narration → upload your receipt → wait for admin approval.
   - Optionally apply a **coupon / scholarship code** before paying.

### Track your payments
- **My Payments** shows every payment, its status, and the receipt number.
- Once a paid payment is confirmed, the course appears in your dashboard.
- Paid payments have a **Request Refund** button (see refund policy).

### Learn
1. Open a course → **Modules** → **Lessons**.
2. Watch / read each lesson. Progress is saved automatically.
3. Use the **AI Assistant** (bottom-right) to ask questions about the current lesson.

### Quizzes & Assignments
- **Quizzes**: take timed quizzes; results are shown with a score.
- **Assignments**: upload your work before the due date.

### Live Classes
- View scheduled **Live Classes** for your courses and join via the meeting link.
- Attendance is recorded by your teacher.

### Certificates
- Complete **100% of a course's lessons** → a certificate is issued automatically.
- **My Certificates** → click **PDF** to download a printable copy, or **Verify** to
  see the public verification page (anyone with the code can check it).

### Notifications & Profile
- Notifications are shown in the bell menu; update your details in **Profile**.

---

## C. Teacher Guide

### Your dashboard
Shows your courses, recent students and quick actions.

### Create a course
Manage courses via the admin or your editors:
1. **Course / Module editor** — structure, videos, descriptions, prices.
2. **Lesson editor** — add video URLs, content, order.
3. **Quiz editor** — questions, options, correct answers, time limit, pass score.
4. **Resource editor** — attach downloadable files to lessons.
5. **Assignments editor** — set coursework and due dates.

### Live classes & attendance
1. **Live Classes** editor — schedule a class with date/time + meeting URL.
2. **Attendance** — pick a scheduled class, mark each enrolled student present/absent.

### Quiz attempts
**Quiz Attempts** shows who took your quizzes and their scores.

---

## D. Admin Guide

### Admin Panel
Access via the admin dashboard. Main sections:

| Section | Purpose |
|---|---|
| **Dashboard**    | Overview statistics |
| **Manage Users** | Search/filter users; activate or deactivate accounts |
| **Manage Courses** | Create/edit courses, teachers, prices |
| **Instructor Applications** | Review + approve/reject instructor applicants |
| **Payments**     | Revenue stats; approve/reject bank transfers; process refunds |
| **Certificates** | Revoke/restore certificates; issue by student email |
| **Reports / Settings** | Analytics and site settings |

### Approving a bank transfer
1. **Payments** → **Pending Bank Transfers**.
2. Check the student's proof (link opens the uploaded file).
3. Click **Approve** (enrolls the student + issues a receipt + notifies)
   or **Reject** with a reason.

### Processing a refund
1. **Payments** → **Pending Refunds**.
2. Click **Approve** or **Reject** with notes.

### Managing users
- Use **Manage Users** to deactivate a violating account. Deactivated accounts
  can no longer log in.

---

## E. Public Verification Page

Anyone can verify a certificate:
1. Open `…/index.php?page=verify`.
2. Enter the certificate code (e.g. `VER-7K2M9F-QX4T8B`).
3. See the holder's name, course, issue date and validity status, plus a QR code.

---

## F. Troubleshooting

| Problem | Solution |
|---|---|
| Can't log in | Check caps lock; use **Forgot password** to reset |
| "Too many failed attempts" | Wait 15 minutes (lockout is server-side) |
| Payment stuck "pending" | Ensure you uploaded proof (bank transfer) or confirm gateway verified online |
| Certificate not issued | Complete ALL active lessons of the course; refresh the page |
| Can't join a live class | Check the meeting URL was shared and the class is `scheduled` |
| Course not accessible after payment | Wait a few seconds for the webhook, then refresh; contact support if not