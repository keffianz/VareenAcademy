@echo off
REM Part 7 — stage, commit, and push threaded-comments work to GitHub.
REM Run from anywhere: just double-click this file.
setlocal
cd /d "%~dp0\.."

REM Drop the repo-local placeholder identity ("Developer <dev@vereenacademy.com>")
REM so new commits fall back to the global Git identity
REM (Abubakar Abdul Raheem <vareengraphics@gmail.com>) for clean GitHub attribution.
git config --unset-all user.name 2>nul
git config --unset-all user.email 2>nul

git add -A
git commit -m "Part 7: threaded comments + E2E test (createPost/addComment/getPost/getComments, migration parent_comment_id, API parent_comment_id, student views, tools/part7_e2e.php + _run_e2e.bat)"
echo.
echo ---- staged/working tree ----
git status --short
echo.
echo ---- push ----
git push origin master
echo.
echo ---- done (errors above, if any, need attention) ----
pause
