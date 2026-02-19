# Email Notifications Every 5 Minutes - Setup Guide

## Overview

Automated system that sends email notifications every 5 minutes for reclamations that have been pending for more than 48 hours without a response.

## How It Works

1. **Every 5 minutes**, the system checks for reclamations:
   - Status: "en_attente"
   - Created more than 48 hours ago
   - No responses yet

2. **Tracks notifications** to avoid spam:
   - Records when last email was sent
   - Only sends if 5+ minutes have passed since last notification
   - Continues sending every 5 minutes until response is added

3. **Stops automatically** when:
   - Admin responds to the reclamation
   - Status changes from "en_attente"

## Quick Start

### Option 1: Run Batch File (Easiest)

Double-click `run_email_notifications.bat` in the project root.

This will:
- Start the notification service
- Check every 5 minutes automatically
- Display status in console window
- Keep running until you close the window

### Option 2: Manual Command

Run once:
```bash
php bin/console app:check-pending-reclamations
```

## What Was Added

### 1. Database Field
- Added `last_notification_sent` to `reclamation` table
- Tracks when the last email was sent for each reclamation

### 2. Updated Command
- Checks if 5 minutes have passed since last notification
- Updates timestamp after each email sent
- Prevents duplicate emails within 5-minute window

### 3. Batch File
- `run_email_notifications.bat` - Runs command every 5 minutes automatically
- Easy to start/stop
- Shows real-time status

## Email Configuration

Your email settings (in `.env`):
```
MAILER_DSN="smtp://sbaiemna04@gmail.com:atfalihgtypbftry@smtp.gmail.com:465"
MAIL_FROM=sbaiemna04@gmail.com
ADMIN_EMAIL=sbaiemna04@gmail.com
```

## Testing

### Test 1: Run Command Once
```bash
php bin/console app:check-pending-reclamations
```

Expected output:
```
Email sent for reclamation #1 (waiting for 96 hours)
Email sent for reclamation #2 (waiting for 95 hours)
...
[OK] Sent 6 email notifications.
```

### Test 2: Run Again Immediately
```bash
php bin/console app:check-pending-reclamations
```

Expected output:
```
Skipping reclamation #1 (last notification sent recently)
Skipping reclamation #2 (last notification sent recently)
...
[INFO] No new notifications to send (all reclamations notified within last 5 minutes).
```

### Test 3: Wait 5 Minutes and Run Again
After 5 minutes, emails will be sent again for pending reclamations.

## Running Automatically

### Method 1: Keep Batch File Running

1. Double-click `run_email_notifications.bat`
2. Leave the window open
3. It will run every 5 minutes automatically
4. Close window to stop

**Pros:**
- Easy to start/stop
- See real-time status
- No configuration needed

**Cons:**
- Must keep window open
- Stops if computer restarts

### Method 2: Windows Task Scheduler (Recommended for Production)

1. Open Task Scheduler
2. Create Basic Task
3. Name: "Reclamation Email Notifications"
4. Trigger: Daily
5. Action: Start a program
   - Program: `C:\Projet-Pi-Java\run_email_notifications.bat`
6. Advanced Settings:
   - Run whether user is logged on or not
   - Run with highest privileges
   - Start on system startup

**Pros:**
- Runs automatically on startup
- Runs in background
- Survives restarts

**Cons:**
- More complex setup
- Harder to see status

### Method 3: Run as Windows Service

For production environments, consider converting to a Windows Service using tools like NSSM (Non-Sucking Service Manager).

## Monitoring

### Check if Running

Look for console window with title containing "run_email_notifications.bat"

### View Logs

The batch file shows real-time output:
```
[19/02/2026 21:50:00] Checking for pending reclamations...
Email sent for reclamation #1 (waiting for 96 hours)
[OK] Sent 1 email notification.

Waiting 5 minutes before next check...
```

### Check Email Inbox

You should receive emails at: sbaiemna04@gmail.com

## Stopping Notifications

### Temporary Stop

Close the batch file window or press Ctrl+C

### Permanent Stop

1. Respond to the reclamation (adds a response)
2. Change reclamation status from "en_attente"
3. Either action will stop emails for that reclamation

## Troubleshooting

### No Emails Received?

1. **Check spam folder**
2. **Verify Gmail settings**:
   - Allow less secure apps OR
   - Use App Password (recommended)
3. **Check .env configuration**
4. **Test email manually**:
   ```bash
   php bin/console app:check-pending-reclamations -v
   ```

### Emails Sent Too Frequently?

The system should only send every 5 minutes. If you're receiving more:
1. Check if multiple instances are running
2. Verify `last_notification_sent` is being updated in database

### Emails Not Sent Every 5 Minutes?

1. Check if batch file is still running
2. Verify no errors in console output
3. Check database for `last_notification_sent` values

## Customization

### Change Interval (from 5 minutes to something else)

**In Command** (`src/Command/CheckPendingReclamationsCommand.php`):
```php
$minutesPassed >= 5; // Change 5 to desired minutes
```

**In Batch File** (`run_email_notifications.bat`):
```batch
timeout /t 300 /nobreak  // 300 seconds = 5 minutes
                         // Change to: 600 for 10 min, 1800 for 30 min, etc.
```

### Change 48 Hour Threshold

In `src/Repository/ReclamationRepository.php`:
```php
$fortyEightHoursAgo = new \DateTime('-48 hours'); // Change to -24, -72, etc.
```

### Add More Recipients

In `src/Command/CheckPendingReclamationsCommand.php`:
```php
$email = (new Email())
    ->from($mailFrom)
    ->to($adminEmail)
    ->cc('another-admin@example.com')  // Add this line
    ->subject('...')
```

## Current Status

✅ Database field added (`last_notification_sent`)
✅ Command updated to track notifications
✅ Batch file created for automatic execution
✅ Tested successfully - 6 emails sent
✅ Ready to run every 5 minutes

## Files Modified/Created

1. **Modified**:
   - `src/Entity/Reclamation.php` - Added `lastNotificationSent` field
   - `src/Command/CheckPendingReclamationsCommand.php` - Added 5-minute tracking
   - `.env` - Updated email configuration

2. **Created**:
   - `run_email_notifications.bat` - Automatic execution script
   - Database column: `last_notification_sent`

## Next Steps

1. **Start the service**:
   ```bash
   # Double-click or run:
   run_email_notifications.bat
   ```

2. **Monitor your email** (sbaiemna04@gmail.com)

3. **Respond to reclamations** to stop notifications

4. **Optional**: Set up Windows Task Scheduler for automatic startup

## Important Notes

⚠️ **Email Frequency**: You will receive an email every 5 minutes for EACH pending reclamation until it's responded to.

⚠️ **Gmail Limits**: Gmail has sending limits. If you have many pending reclamations, consider:
- Responding to them promptly
- Increasing the interval (e.g., 15 or 30 minutes)
- Using a dedicated email service

⚠️ **Production**: For production, use a proper email service (SendGrid, Mailgun, etc.) instead of Gmail.
