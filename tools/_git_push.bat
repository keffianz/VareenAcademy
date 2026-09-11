@echo off
REM Push all Part 1-7 fixes to GitHub (https://github.com/keffianz/VareenAcademy).
REM Run from anywhere: just double-click this file.
setlocal
cd /d "%~dp0\.."

REM Drop the repo-local placeholder identity ("Developer <dev@vereenacademy.com>")
REM so new commits fall back to the global Git identity
REM (Abubakar Abdul Raheem <vareengraphics@gmail.com>) for clean GitHub attribution.
git config --unset-all user.name 2>nul
git config --unset-all user.email 2>nul

git add -A
git commit -m "Part 1-7 bug fixes: admin/teacher dashboard chrome (dash-shell removed, quiz-attempts wrapped); 500 fixes (enrollments.progress_percent->progress, submissions table, student_id join, attendance API actions, resource-editor route, live-classes meeting_url); verified contact/apply -> admin inbox + email, admin create-account (bcrypt) login flow, AI Control Center key manager, threaded community comments + E2E test"
echo.
echo ---- staged/working tree ----
git status --short
echo.
echo ---- push ----
git push origin master
echo.
echo ---- done (errors above, if any, need attention) ----
pause
