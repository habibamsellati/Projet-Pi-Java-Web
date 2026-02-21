<?php

namespace App\Command;

use App\Service\SmsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-twilio-sms',
    description: 'Test Twilio SMS sending functionality',
)]
class TestTwilioSmsCommand extends Command
{
    private SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        parent::__construct();
        $this->smsService = $smsService;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('phone', InputArgument::REQUIRED, 'Phone number to send SMS to (e.g., +21612345678 or 12345678)')
            ->addArgument('message', InputArgument::OPTIONAL, 'Custom message to send', 'Test SMS from AfkArt - Twilio is working!')
            ->setHelp(<<<'HELP'
This command tests the Twilio SMS functionality.

Usage examples:
  php bin/console app:test-twilio-sms +21612345678
  php bin/console app:test-twilio-sms 12345678
  php bin/console app:test-twilio-sms +21612345678 "Custom test message"

The command will:
1. Check if TWILIO_DSN is configured
2. Normalize the phone number (add +216 if needed)
3. Send a test SMS
4. Display the result
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $phone = $input->getArgument('phone');
        $message = $input->getArgument('message');

        $io->title('Twilio SMS Test');

        // Check TWILIO_DSN configuration
        $twilioDsn = $_ENV['TWILIO_DSN'] ?? $_SERVER['TWILIO_DSN'] ?? '';
        
        $io->section('Configuration Check');
        if (empty($twilioDsn)) {
            $io->error('TWILIO_DSN is not configured in .env file');
            $io->note('Please add TWILIO_DSN to your .env file:');
            $io->text('TWILIO_DSN=twilio://ACCOUNT_SID:AUTH_TOKEN@default?from=PHONE_NUMBER');
            return Command::FAILURE;
        }

        if (str_starts_with($twilioDsn, 'fake://')) {
            $io->warning('TWILIO_DSN is set to fake:// - SMS will be simulated, not actually sent');
        } else {
            $io->success('TWILIO_DSN is configured');
            
            // Parse and display Twilio configuration (without showing full credentials)
            if (preg_match('/twilio:\/\/([^:]+):([^@]+)@default\?from=(.+)/', $twilioDsn, $matches)) {
                $accountSid = $matches[1];
                $fromNumber = urldecode($matches[3]);
                
                $io->text([
                    'Account SID: ' . substr($accountSid, 0, 10) . '...',
                    'From Number: ' . $fromNumber,
                ]);
            }
        }

        // Normalize phone number
        $normalizedPhone = $this->normalizePhoneNumber($phone);
        
        $io->section('Phone Number');
        $io->text([
            'Original: ' . $phone,
            'Normalized: ' . $normalizedPhone,
        ]);

        if (!$normalizedPhone) {
            $io->error('Invalid phone number format');
            return Command::FAILURE;
        }

        // Send SMS
        $io->section('Sending SMS');
        $io->text('Message: ' . $message);
        $io->newLine();
        
        $io->text('Attempting to send SMS...');
        
        // Test direct Twilio call to get detailed error
        $io->text('Testing Twilio connection...');
        
        try {
            // Try to send via SmsService
            $result = $this->smsService->sendSms($normalizedPhone, $message);
            
            if ($result) {
                $io->success('✅ SMS sent successfully!');
                $io->text([
                    'Recipient: ' . $normalizedPhone,
                    'Message: ' . $message,
                    'Status: Delivered to Twilio',
                ]);
                
                $io->note([
                    'The SMS has been sent to Twilio.',
                    'Check your phone to confirm receipt.',
                    'If you don\'t receive it, check:',
                    '  - Phone number is correct',
                    '  - Twilio account has credits',
                    '  - Phone number is verified in Twilio (for trial accounts)',
                ]);
                
                return Command::SUCCESS;
            } else {
                $io->error('❌ Failed to send SMS');
                
                // Try to get more details by testing Twilio directly
                $io->section('Detailed Diagnostics');
                
                try {
                    $smsMessage = new \Symfony\Component\Notifier\Message\SmsMessage($normalizedPhone, $message);
                    $io->text('SmsMessage object created successfully');
                    
                    // Check if texter is available
                    $io->text('Checking Symfony Notifier configuration...');
                    
                    $io->warning([
                        'The SMS service returned false.',
                        'This usually means:',
                        '  1. Twilio credentials are invalid',
                        '  2. Twilio account has no credits',
                        '  3. Phone number is not verified (trial accounts)',
                        '  4. Network connectivity issue',
                    ]);
                    
                    $io->note([
                        'To debug further:',
                        '  1. Verify your Twilio credentials at https://console.twilio.com',
                        '  2. Check account balance',
                        '  3. For trial accounts, verify the recipient phone number',
                        '  4. Check var/log/dev.log for detailed error messages',
                    ]);
                    
                } catch (\Exception $diagException) {
                    $io->error('Diagnostic error: ' . $diagException->getMessage());
                }
                
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $io->error('❌ Exception occurred while sending SMS');
            $io->text([
                'Error Type: ' . get_class($e),
                'Error Message: ' . $e->getMessage(),
                'File: ' . $e->getFile() . ':' . $e->getLine(),
            ]);
            
            if ($e->getPrevious()) {
                $io->section('Previous Exception');
                $io->text([
                    'Type: ' . get_class($e->getPrevious()),
                    'Message: ' . $e->getPrevious()->getMessage(),
                ]);
            }
            
            return Command::FAILURE;
        }
    }

    private function normalizePhoneNumber(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $phone = trim($phone);
        if ($phone === '') {
            return null;
        }

        // Already has country code
        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        // Remove all non-digit characters
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return null;
        }

        // Already has Tunisia code
        if (str_starts_with($digits, '216')) {
            return '+' . $digits;
        }

        // 8-digit Tunisian number
        if (strlen($digits) === 8) {
            return '+216' . $digits;
        }

        // Other international number
        return '+' . $digits;
    }
}
