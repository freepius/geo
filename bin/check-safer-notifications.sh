#!/bin/bash

# Script to check SAFER notifications and send desktop notifications
# Usage: ./bin/check-safer-notifications.sh

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_DIR" || exit 1

# Communes to monitor
COMMUNES=("Divajeu" "Chabrillan")

# Run the command and get JSON output
RESULT=$(php bin/console safer:check-notifications --json "${COMMUNES[@]}" 2>/dev/null | grep -A 100 '{')

# Check if we have results
if [ -n "$RESULT" ] && [ "$RESULT" != "{}" ]; then
    # Parse JSON to create notification message
    MESSAGE=""
    NEW_COUNT=0

    # Extract information using jq (if available) or simple parsing
    if command -v jq &> /dev/null; then
        # Use jq for better parsing
        while IFS= read -r commune; do
            COUNT=$(echo "$RESULT" | jq -r ".[\"$commune\"].count")
            IS_NEW=$(echo "$RESULT" | jq -r ".[\"$commune\"].isNew")
            URL=$(echo "$RESULT" | jq -r ".[\"$commune\"].url")

            if [ "$IS_NEW" = "true" ]; then
                MESSAGE="${MESSAGE}🆕 "
                ((NEW_COUNT++))
            fi
            MESSAGE="${MESSAGE}${commune}: ${COUNT} notification(s)\n"
        done < <(echo "$RESULT" | jq -r 'keys[]')
    else
        # Simple parsing without jq
        MESSAGE=$(echo "$RESULT" | grep -o '"[^"]*": {' | sed 's/": {//' | tr -d '"')
        NEW_COUNT=1  # Assume new if we found results
    fi

    # Send desktop notification if there are new notifications
    if [ $NEW_COUNT -gt 0 ]; then
        notify-send -u critical -i dialog-warning "SAFER - Nouvelles notifications" \
            "$(echo -e "$MESSAGE")\n\nConsultez: https://iframe-annonces-legales.safer.fr/ara/26/liste/notification"
    fi

    # Optional: Send email (uncomment and configure if needed)
    # if [ $NEW_COUNT -gt 0 ]; then
    #     echo -e "Nouvelles notifications SAFER:\n\n$MESSAGE" | \
    #         mail -s "SAFER: Nouvelles notifications" votre.email@example.com
    # fi

    # Log the result
    echo "$(date '+%Y-%m-%d %H:%M:%S') - Found notifications for ${COMMUNES[*]}" >> "$PROJECT_DIR/var/log/safer-notifications.log"
else
    echo "$(date '+%Y-%m-%d %H:%M:%S') - No notifications found" >> "$PROJECT_DIR/var/log/safer-notifications.log"
fi
