<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;

class TurnstileVerifier
{
    public function getSiteKey(): string
    {
        return $this->getEnvValue('TURNSTILE_SITE_KEY');
    }

    public function isConfigured(): bool
    {
        return $this->getSiteKey() !== '' && $this->getSecretKey() !== '';
    }

    public function verifyRequest(Request $request): bool
    {
        // Strict mode: if Turnstile is not configured, fail verification.
        if (!$this->isConfigured()) {
            return false;
        }

        $token = (string) $request->request->get('cf-turnstile-response', '');
        if ($token === '') {
            return false;
        }

        return $this->verifyToken($token, $request->getClientIp());
    }

    public function verifyToken(string $token, ?string $remoteIp = null): bool
    {
        $payload = [
            'secret' => $this->getSecretKey(),
            'response' => $token,
        ];

        if (is_string($remoteIp) && $remoteIp !== '') {
            $payload['remoteip'] = $remoteIp;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($payload),
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);

        $raw = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);
        if ($raw === false) {
            return false;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return false;
        }

        return (bool) ($decoded['success'] ?? false);
    }

    private function getSecretKey(): string
    {
        return $this->getEnvValue('TURNSTILE_SECRET_KEY');
    }

    private function getEnvValue(string $key): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if (!is_string($value)) {
            return '';
        }

        return trim($value);
    }
}
