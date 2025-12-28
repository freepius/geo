#!/bin/bash

# Script to check SAFER notifications and send email alerts
# Requires: mailutils or sendmail configured
# Usage: ./bin/check-safer-notifications-email.sh your.email@example.com

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_DIR" || exit 1

# Get email from argument or use default
EMAIL="${1:-votre.email@example.com}"

# Communes to monitor
COMMUNES=("Divajeu" "Chabrillan")

# Run the command and get JSON output
RESULT=$(php bin/console safer:check-notifications --json "${COMMUNES[@]}" 2>/dev/null | grep -A 100 '{')

# Check if we have results
if [ -n "$RESULT" ] && [ "$RESULT" != "{}" ]; then
    # Parse JSON to create email body
    EMAIL_BODY="Bonjour,\n\nDe nouvelles notifications SAFER ont été détectées pour vos communes:\n\n"
    NEW_COUNT=0

    if command -v jq &> /dev/null; then
        while IFS= read -r commune; do
            COUNT=$(echo "$RESULT" | jq -r ".[\"$commune\"].count")
            IS_NEW=$(echo "$RESULT" | jq -r ".[\"$commune\"].isNew")
            URL=$(echo "$RESULT" | jq -r ".[\"$commune\"].url")

            EMAIL_BODY="${EMAIL_BODY}📍 ${commune}:\n"
            EMAIL_BODY="${EMAIL_BODY}   - Nombre de notifications: ${COUNT}\n"

            if [ "$IS_NEW" = "true" ]; then
                EMAIL_BODY="${EMAIL_BODY}   - Statut: 🆕 NOUVEAU\n"
                ((NEW_COUNT++))
            else
                EMAIL_BODY="${EMAIL_BODY}   - Statut: Déjà consulté\n"
            fi

            EMAIL_BODY="${EMAIL_BODY}   - URL: ${URL}\n\n"
        done < <(echo "$RESULT" | jq -r 'keys[]')
    fi

    EMAIL_BODY="${EMAIL_BODY}\nLien direct: https://iframe-annonces-legales.safer.fr/ara/26/liste/notification\n\n"
    EMAIL_BODY="${EMAIL_BODY}Cordialement,\nVotre système de veille SAFER"

    # Send email only if there are new notifications
    if [ $NEW_COUNT -gt 0 ]; then
        if command -v mail &> /dev/null; then
            echo -e "$EMAIL_BODY" | mail -s "🔔 SAFER: Nouvelles notifications détectées" "$EMAIL"
            echo "$(date '+%Y-%m-%d %H:%M:%S') - Email sent to $EMAIL" >> "$PROJECT_DIR/var/log/safer-notifications.log"
        else
            echo "Error: 'mail' command not found. Please install mailutils: sudo apt install mailutils"
            exit 1
        fi
    fi

    echo "$(date '+%Y-%m-%d %H:%M:%S') - Found notifications ($NEW_COUNT new)" >> "$PROJECT_DIR/var/log/safer-notifications.log"
else
    echo "$(date '+%Y-%m-%d %H:%M:%S') - No notifications found" >> "$PROJECT_DIR/var/log/safer-notifications.log"
fi
