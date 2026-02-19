# Reclamation Email Notification System

## Overview

Automatic email notification system that alerts admins when reclamations have been pending for more than 48 hours without a response.

## Features

- Checks for reclamations in "en_attente" status
- Only notifies for reclamations older than 48 hours
- Only sends emails for reclamations with no responses yet
- Sends detailed email with reclamation information
- Includes direct link to respond to the reclamation

## Configuration

### Email Settings

Configured in `.env`:
```
MAILER_DSN="smtp://sbaiemna04@gmail.com:atfalihgtypbftry@smtp.gmail.com:465"
MAIL_FROM=sbaiemna04@gmail.com
ADMIN_EMAIL=sbaiemna04@gmail.com
```

### Email Content

Each notification email includes:
- ⚠️ Urgent warning header
- Reclamation ID and title
- Current status
- Creation date
- Hours waiting (highlighted in red)
- Customer information (name, email)
- Full description
- Direct link to view and respond

## Manual Execution

Run the command manually:
```bash
php bin/console app:check-pending-reclamations
```

## Automatic Scheduling

### Option 1: Windows Task Scheduler

1. Open Task Scheduler
2. Create Basic Task
3. Name: "Check Pending Reclamations"
4. Trigger: Daily at specific time (e.g., 9:00 AM)
5. Action: Start a program
   - Program: `php`
   - Arguments: `bin/console app:check-pending-reclamations`
   - Start in: `C:\Projet-Pi-Java`
6. Set to run every 6 hours (in advanced settings)

### Option 2: Cron Job (Linux/Mac)

Add to crontab:
```bash
# Run every 6 hours
0 */6 * * * cd /path/to/project && php bin/console app:check-pending-reclamations
```

### Option 3: Symfony Messenger (Recommended for Production)

Install messenger component:
```bash
composer require symfony/messenger
```

Configure scheduled messages in `config/packages/messenger.yaml`

## How It Works

1. **Command runs** (manually or scheduled)
2. **Query database** for reclamations:
   - Status = "en_attente"
   - Created more than 48 hours ago
   - No responses yet
3. **For each pending reclamation**:
   - Calculate hours waiting
   - Generate HTML email
   - Send to admin email
4. **Log results** to console

## Repository Method

`ReclamationRepository::findPendingOver48Hours()`

Returns reclamations matching:
- Status: "en_attente"
- Created: <= 48 hours ago
- Responses: None (LEFT JOIN with NULL check)

## Testing

### Test with Current Data

```bash
php bin/console app:check-pending-reclamations
```

### Expected Output

```
Email sent for reclamation #1 (waiting for 96 hours)
Email sent for reclamation #2 (waiting for 95 hours)
...
[OK] Processed X pending reclamations.
```

### Check Your Email

You should receive emails at: sbaiemna04@gmail.com

## Troubleshooting

### No Emails Received?

1. Check spam folder
2. Verify Gmail settings allow "Less secure app access" or use App Password
3. Check `.env` configuration
4. Run command with verbose output:
   ```bash
   php bin/console app:check-pending-reclamations -v
   ```

### Gmail App Password

If using 2FA on Gmail:
1. Go to Google Account settings
2. Security → 2-Step Verification
3. App passwords
4. Generate new app password
5. Use in `.env` MAILER_DSN

### Test Email Sending

Create a test command:
```bash
php bin/console app:check-pending-reclamations
```

Check console output for errors.

## Customization

### Change 48 Hour Threshold

Edit `src/Repository/ReclamationRepository.php`:
```php
$fortyEightHoursAgo = new \DateTime('-48 hours'); // Change to -24 hours, -72 hours, etc.
```

### Change Email Template

Edit `src/Command/CheckPendingReclamationsCommand.php`:
- Method: `generateEmailContent()`
- Modify HTML template as needed

### Add More Recipients

Edit command to send to multiple admins:
```php
$email = (new Email())
    ->from($mailFrom)
    ->to($adminEmail)
    ->cc('another-admin@example.com')
    ->bcc('manager@example.com')
    // ...
```

## Files Created/Modified

1. **Created**:
   - `src/Command/CheckPendingReclamationsCommand.php` - Main command
   
2. **Modified**:
   - `src/Repository/ReclamationRepository.php` - Added `findPendingOver48Hours()`
   - `.env` - Updated email configuration

## Production Recommendations

1. Use environment variables for sensitive data
2. Set up proper cron job or task scheduler
3. Monitor email delivery
4. Log all notifications
5. Consider using a queue system for large volumes
6. Add rate limiting to prevent spam

## Current Status

✅ Email configuration set up
✅ Command created and tested
✅ Repository method added
✅ Successfully sent 6 test emails
⏳ Automatic scheduling (needs to be configured)

## Next Steps

1. Set up Windows Task Scheduler or cron job
2. Monitor email delivery
3. Adjust timing if needed
4. Consider adding email templates
