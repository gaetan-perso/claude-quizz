#!/bin/sh
OUTFILE="C:/projets/IA/app_quizz_claude/.claude/tmp/refresh_out.txt"
GH="C:/Program Files/GitHub CLI/gh.exe"

{
  echo "=== Refreshing token with project scopes ==="
  "$GH" auth refresh -h github.com -s read:project,project 2>&1
  echo "RC: $?"
} > "$OUTFILE" 2>&1
