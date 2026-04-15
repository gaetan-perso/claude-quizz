@echo off
"C:\Program Files\GitHub CLI\gh.exe" auth status
echo EXIT: %ERRORLEVEL%
"C:\Program Files\GitHub CLI\gh.exe" issue list --repo gaetan-perso/claude-quizz --limit 10 --state open
