#!/bin/sh
OUTFILE="C:/projets/IA/app_quizz_claude/.claude/tmp/board_current.txt"
GH="C:/Program Files/GitHub CLI/gh.exe"

"$GH" api graphql -f query='
{
  node(id: "PVT_kwHOAG1LKc4BUJ8p") {
    ... on ProjectV2 {
      items(first: 100) {
        nodes {
          content {
            ... on Issue {
              number
              title
            }
          }
        }
      }
    }
  }
}' > "$OUTFILE" 2>&1

echo "RC: $?" >> "$OUTFILE"
