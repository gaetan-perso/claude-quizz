#!/bin/sh
OUTFILE="C:/projets/IA/app_quizz_claude/.claude/tmp/gh_output3.txt"

{
  echo "=== GH VERSION ==="
  "C:/Program Files/GitHub CLI/gh.exe" --version 2>&1
  echo "RC: $?"

  echo "=== AUTH STATUS ==="
  "C:/Program Files/GitHub CLI/gh.exe" auth status 2>&1
  echo "RC: $?"

  echo "=== ISSUE LIST ==="
  "C:/Program Files/GitHub CLI/gh.exe" issue list --repo gaetan-perso/claude-quizz --limit 50 --state open --json number,title 2>&1
  echo "RC: $?"

} > "$OUTFILE" 2>&1
echo "Script completed"
