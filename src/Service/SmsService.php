<?php

namespace App\Service;

use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\TexterInterface;
use Psr\Log\LoggerInterface;

/**
 * SmsService — Envoi de SMS via le bundle Symfony Notifier (Twilio)
 */
class SmsService
{
    private ?TexterInterface $texter;
    private ?LoggerInterface $logger;

    public function __construct(?TexterInterface $texter = null, ?LoggerInterface $logger = null)
    {
        $this->texter = $texter;
        $this->logger = $logger;
    }

    /**
     * Envoie un SMS en utilisant le bundle Symfony Notifier.
     */
    public function sendSms(string $to, string $message): bool
    {
        if (!$this->texter) {
            $this->log('error', 'SmsService: TexterInterface non disponible.');
            return false;
        }

        $dsn = $_ENV['TWILIO_DSN'] ?? $_SERVER['TWILIO_DSN'] ?? '';
        if (empty($dsn) || str_starts_with($dsn, 'fake://')) {
            $this->log('warning', 'SmsService: TWILIO_DSN non configuré — SMS simulé.', ['to' => $to]);
            return false;
        }

        try {
            $sms = new SmsMessage($to, $message);
            $this->texter->send($sms);

            $this->log('info', 'SMS envoyé via Symfony Notifier (Twilio).', [
                'to' => $to,
                'message' => substr($message, 0, 60) . '...'
            ]);

            return true;
        } catch (\Exception $e) {
            $errorDetails = [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'to' => $to,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
            
            if ($e->getPrevious()) {
                $errorDetails['previous_error'] = $e->getPrevious()->getMessage();
                $errorDetails['previous_class'] = get_class($e->getPrevious());
            }
            
            $this->log('error', 'Échec envoi SMS via Notifier.', $errorDetails);
            
            // Also output to stderr for immediate visibility
            error_log(sprintf(
                '[SmsService] Failed to send SMS to %s: %s (%s)',
                $to,
                $e->getMessage(),
                get_class($e)
            ));
            
            return false;
        }
    }

    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->{$level}($message, $context);
        }
    }
}
