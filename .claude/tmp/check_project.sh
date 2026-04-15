#!/bin/sh
OUTFILE="C:/projets/IA/app_quizz_claude/.claude/tmp/project_check.txt"
GH="C:/Program Files/GitHub CLI/gh.exe"

{
  echo "=== List projects for user ==="
  "$GH" project list --owner gaetan-perso 2>&1
  echo "RC: $?"

  echo "=== Try project view ==="
  "$GH" project view 2 --owner gaetan-perso 2>&1
  echo "RC: $?"

  echo "=== Token scopes ==="
  "$GH" auth status 2>&1

  echo "=== API viewer (check token scope) ==="
  "$GH" api graphql -f query='{ viewer { login } }' 2>&1

} > "$OUTFILE" 2>&1
