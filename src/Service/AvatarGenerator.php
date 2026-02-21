<?php

namespace App\Service;

class AvatarGenerator
{
    public function generate(string $nom, string $prenom, ?string $sexe = null): string
    {
        $seed = trim(strtoupper($nom . ' ' . $prenom));
        if ($seed === '') {
            $seed = 'AFKART';
        }

        $hash = substr(hash('sha256', $seed . '|' . strtoupper((string) $sexe)), 0, 24);

        $uploadDir = \dirname(__DIR__, 2) . '/public/uploads/avatars';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        $pngFilename = 'avatar_' . $hash . '.png';
        $pngPath = $uploadDir . '/' . $pngFilename;

        if (file_exists($pngPath)) {
            return '/uploads/avatars/' . $pngFilename;
        }

        // Always try Hugging Face first, even if an old SVG exists from previous local generation.
        $hfImage = $this->generateWithHuggingFace($nom, $prenom, $sexe);
        if ($hfImage !== null) {
            @file_put_contents($pngPath, $hfImage);
            return '/uploads/avatars/' . $pngFilename;
        }

        return $this->defaultAvatarBySexe($sexe);
    }

    private function generateWithHuggingFace(string $nom, string $prenom, ?string $sexe): ?string
    {
        $apiKey = $this->getEnvValue('HF_API_TOKEN');
        if ($apiKey === '') {
            return null;
        }

        $model = $this->getEnvValue('HF_IMAGE_MODEL');
        if ($model === '') {
            $model = 'stabilityai/stable-diffusion-xl-base-1.0';
        }

        $genderLabel = strtoupper((string) $sexe);
        if (!in_array($genderLabel, ['HOMME', 'FEMME', 'AUTRE'], true)) {
            $genderLabel = 'AUTRE';
        }

        $displayName = trim($prenom . ' ' . $nom);
        if ($displayName === '') {
            $displayName = 'Utilisateur';
        }

        $prompt = 'Create an artistic profile avatar portrait, head and shoulders centered, '
            . 'high-quality digital illustration, elegant composition, refined color palette, '
            . 'expressive but clean facial style, soft cinematic lighting, smooth gradients, '
            . 'professional character design, modern art-platform aesthetic. '
            . 'Use a transparent or plain minimal background, no text, no logos, no watermark. '
            . 'Gender: ' . $genderLabel . '. '
            . 'Identity cue for consistency: ' . $displayName . '.';

        $url = 'https://router.huggingface.co/hf-inference/models/' . rawurlencode($model);
        $payload = [
            'inputs' => $prompt,
            'parameters' => [
                'negative_prompt' => 'blurry, low quality, distorted face, extra eyes, extra mouth, deformed hands, watermark, text, logo, cropped head, ugly, noisy background',
                'guidance_scale' => 7.5,
                'num_inference_steps' => 35,
            ],
            'options' => [
                'wait_for_model' => true,
            ],
        ];

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$apiKey}\r\n",
                'content' => json_encode($payload, JSON_UNESCAPED_SLASHES),
                'ignore_errors' => true,
                'timeout' => 60,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        if ($body === false || $body === '') {
            return null;
        }

        // Hugging Face inference returns raw image bytes on success.
        $contentType = '';
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $line) {
                if (stripos($line, 'Content-Type:') === 0) {
                    $contentType = strtolower(trim(substr($line, strlen('Content-Type:'))));
                    break;
                }
            }
        }

        if (str_contains($contentType, 'image/')) {
            return $body;
        }

        // If response is JSON (error/loading), fallback to local avatar.
        return null;
    }

    private function defaultAvatarBySexe(?string $sexe): string
    {
        $normalized = strtoupper((string) $sexe);
        if ($normalized === 'HOMME') {
            return '/uploads/avatars/default_homme.svg';
        }
        if ($normalized === 'FEMME') {
            return '/uploads/avatars/default_femme.svg';
        }
        return '/uploads/avatars/default_autre.svg';
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
