#!/bin/bash
GH="C:/Program Files/GitHub CLI/gh.exe"
PROJECT_ID="PVT_kwHOAG1LKc4BUJ8p"
FIELD_ID="PVTSSF_lAHOAG1LKc4BUJ8pzhBUBco"
OPTION_ID="f75ad846"
LOGFILE="C:/projets/IA/app_quizz_claude/.claude/tmp/add_issues_log.txt"

echo "" > "$LOGFILE"

for i in $(seq 37 64); do
  echo "Processing issue #$i..." >> "$LOGFILE"

  # Step 1: get node_id
  NODE_ID=$("$GH" api repos/gaetan-perso/claude-quizz/issues/$i --jq '.node_id' 2>> "$LOGFILE")

  if [ -z "$NODE_ID" ]; then
    echo "  ERROR: Could not get node_id for issue #$i" >> "$LOGFILE"
    continue
  fi
  echo "  node_id: $NODE_ID" >> "$LOGFILE"

  # Step 2: add to board
  ITEM_ID=$("$GH" api graphql -f query='mutation($projectId:ID!,$contentId:ID!){addProjectV2ItemById(input:{projectId:$projectId,contentId:$contentId}){item{id}}}' \
    -f projectId="$PROJECT_ID" -f contentId="$NODE_ID" --jq '.data.addProjectV2ItemById.item.id' 2>> "$LOGFILE")

  if [ -z "$ITEM_ID" ]; then
    echo "  ERROR: Could not add issue #$i to board" >> "$LOGFILE"
    continue
  fi
  echo "  item_id: $ITEM_ID" >> "$LOGFILE"

  # Step 3: set status to Todo
  RESULT=$("$GH" api graphql -f query='mutation($projectId:ID!,$itemId:ID!,$fieldId:ID!,$optionId:String!){updateProjectV2ItemFieldValue(input:{projectId:$projectId,itemId:$itemId,fieldId:$fieldId,value:{singleSelectOptionId:$optionId}}){projectV2Item{id}}}' \
    -f projectId="$PROJECT_ID" \
    -f itemId="$ITEM_ID" \
    -f fieldId="$FIELD_ID" \
    -f optionId="$OPTION_ID" --jq '.data.updateProjectV2ItemFieldValue.projectV2Item.id' 2>> "$LOGFILE")

  if [ -z "$RESULT" ]; then
    echo "  ERROR: Could not set status for issue #$i" >> "$LOGFILE"
  else
    echo "  SUCCESS: Issue #$i added and set to Todo" >> "$LOGFILE"
  fi
done

echo "Done processing all issues." >> "$LOGFILE"
