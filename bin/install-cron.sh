#!/bin/bash

# Script to install the SAFER notification checker as a cron job
# This will check for notifications every day at 9:00 AM

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
CHECK_SCRIPT="$SCRIPT_DIR/check-safer-notifications.sh"

# Make the check script executable
chmod +x "$CHECK_SCRIPT"

# Create log directory if it doesn't exist
mkdir -p "$PROJECT_DIR/var/log"

# Cron job line (runs every day at 9:00 AM)
CRON_JOB="0 9 * * * DISPLAY=:0 DBUS_SESSION_BUS_ADDRESS=unix:path=/run/user/$(id -u)/bus $CHECK_SCRIPT"

# Check if cron job already exists
if crontab -l 2>/dev/null | grep -q "check-safer-notifications.sh"; then
    echo "Cron job already exists. Updating..."
    (crontab -l 2>/dev/null | grep -v "check-safer-notifications.sh"; echo "$CRON_JOB") | crontab -
else
    echo "Adding new cron job..."
    (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
fi

echo "✓ Cron job installed successfully!"
echo "✓ SAFER notifications will be checked daily at 9:00 AM"
echo ""
echo "To test the notification script manually, run:"
echo "  $CHECK_SCRIPT"
echo ""
echo "To view the cron job, run:"
echo "  crontab -l"
echo ""
echo "To remove the cron job, run:"
echo "  crontab -e"
echo "  (then delete the line containing 'check-safer-notifications.sh')"
