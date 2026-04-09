#!/usr/bin/env bash
# move-ticket.sh — Déplace un ticket GitHub vers une colonne du board projet
#
# Usage:
#   move-ticket.sh <issue_number> <column>
#
# Colonnes disponibles : "Todo" | "In Progress" | "Done"
#
# Exemple:
#   move-ticket.sh 12 "In Progress"
#   move-ticket.sh 12 "Done"

set -euo pipefail

ISSUE_NUMBER="${1:?Usage: move-ticket.sh <issue_number> <column>}"
COLUMN="${2:?Usage: move-ticket.sh <issue_number> <column>}"

PROJECT_ID="PVT_kwHOAG1LKc4BUJ8p"
STATUS_FIELD_ID="PVTSSF_lAHOAG1LKc4BUJ8pzhBUBco"
REPO_OWNER="gaetan-perso"
REPO_NAME="app_quizz_claude"

# Map column name → option ID
case "$COLUMN" in
  "Todo")        OPTION_ID="f75ad846" ;;
  "In Progress") OPTION_ID="47fc9ee4" ;;
  "Done")        OPTION_ID="98236657" ;;
  *)
    echo "Colonne inconnue: '$COLUMN'. Valeurs valides: 'Todo', 'In Progress', 'Done'" >&2
    exit 1
    ;;
esac

# 1. Récupère l'item ID du projet pour cet issue
ITEM_ID=$(gh api graphql -f query="
{
  repository(owner: \"$REPO_OWNER\", name: \"$REPO_NAME\") {
    issue(number: $ISSUE_NUMBER) {
      projectItems(first: 10) {
        nodes {
          id
          project {
            id
          }
        }
      }
    }
  }
}" --jq ".data.repository.issue.projectItems.nodes[] | select(.project.id == \"$PROJECT_ID\") | .id")

if [ -z "$ITEM_ID" ]; then
  echo "Erreur: issue #$ISSUE_NUMBER introuvable dans le projet (project ID: $PROJECT_ID)" >&2
  echo "Vérifie que l'issue est bien ajoutée au board." >&2
  exit 1
fi

# 2. Met à jour le statut
gh api graphql -f query="
mutation {
  updateProjectV2ItemFieldValue(input: {
    projectId: \"$PROJECT_ID\"
    itemId: \"$ITEM_ID\"
    fieldId: \"$STATUS_FIELD_ID\"
    value: { singleSelectOptionId: \"$OPTION_ID\" }
  }) {
    projectV2Item {
      id
    }
  }
}" > /dev/null

echo "OK: issue #$ISSUE_NUMBER déplacée vers '$COLUMN'"
