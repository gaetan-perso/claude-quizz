GH="C:/Program Files/GitHub CLI/gh.exe"
PROJECT_ID="PVT_kwHOAG1LKc4BUJ8p"
FIELD_ID="PVTSSF_lAHOAG1LKc4BUJ8pzhBUBco"
OPTION_ID="f75ad846"
LOG="C:/projets/IA/app_quizz_claude/.claude/tmp/results.txt"

echo "Starting..." > "$LOG"

process_issue() {
  local NUM=$1
  echo "=== Issue #$NUM ===" >> "$LOG"

  local NODE_ID
  NODE_ID=$("$GH" api "repos/gaetan-perso/claude-quizz/issues/$NUM" --jq '.node_id' 2>> "$LOG")
  echo "node_id=$NODE_ID" >> "$LOG"

  if [ -z "$NODE_ID" ]; then
    echo "SKIP: no node_id" >> "$LOG"
    return
  fi

  local ITEM_ID
  ITEM_ID=$("$GH" api graphql \
    -f query='mutation($p:ID!,$c:ID!){addProjectV2ItemById(input:{projectId:$p,contentId:$c}){item{id}}}' \
    -f p="$PROJECT_ID" -f c="$NODE_ID" \
    --jq '.data.addProjectV2ItemById.item.id' 2>> "$LOG")
  echo "item_id=$ITEM_ID" >> "$LOG"

  if [ -z "$ITEM_ID" ]; then
    echo "SKIP: no item_id" >> "$LOG"
    return
  fi

  "$GH" api graphql \
    -f query='mutation($p:ID!,$i:ID!,$f:ID!,$o:String!){updateProjectV2ItemFieldValue(input:{projectId:$p,itemId:$i,fieldId:$f,value:{singleSelectOptionId:$o}}){projectV2Item{id}}}' \
    -f p="$PROJECT_ID" -f i="$ITEM_ID" -f f="$FIELD_ID" -f o="$OPTION_ID" \
    >> "$LOG" 2>&1

  echo "OK" >> "$LOG"
}

for n in 37 38 39 40 41 42 43 44 45 46 47 48 49 50 51 52 53 54 55 56 57 58 59 60 61 62 63 64; do
  process_issue "$n"
done

echo "FINISHED" >> "$LOG"
