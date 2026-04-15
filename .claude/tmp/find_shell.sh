#!/bin/sh
# Write to a file using shell builtins only
OUTFILE="C:/projets/IA/app_quizz_claude/.claude/tmp/shell_info.txt"

{
  echo "SHELL: $SHELL"
  echo "PATH: $PATH"
  echo "HOME: $HOME"
  echo "USER: $USER"
  echo "OS: $(uname -a 2>&1)"
  echo "which: $(which which 2>&1)"
  echo "type ls: $(type ls 2>&1)"
  echo "type gh: $(type gh 2>&1)"
  echo "type php: $(type php 2>&1)"
  echo "type node: $(type node 2>&1)"
  echo "type composer: $(type composer 2>&1)"
  echo "---compgen---"
  compgen -c 2>&1 | head -20
} > "$OUTFILE" 2>&1
