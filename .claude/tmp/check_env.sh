#!/usr/bin/env sh
echo "PATH: $PATH"
which sh 2>&1
which bash 2>&1
which pwsh 2>&1
which powershell 2>&1
ls /usr/bin/ 2>&1 | head -20
ls /bin/ 2>&1 | head -20
echo "---"
ls "C:/Program Files/GitHub CLI/" 2>&1
echo "---"
"C:/Program Files/GitHub CLI/gh.exe" --version 2>&1
