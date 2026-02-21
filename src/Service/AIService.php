<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class AIService
{
    private string $projectDir;
    private string $openAiApiKey;
    private string $openAiModel;
    private string $hfApiKey;

    public function __construct(ParameterBagInterface $params)
    {
        $this->projectDir = (string) $params->get('kernel.project_dir');
        $this->openAiApiKey = (string) (
            $_SERVER['OPENAI_API_KEY']
            ?? getenv('OPENAI_API_KEY')
            ?? $_ENV['OPENAI_API_KEY']
            ?? ''
        );
        $this->openAiModel = (string) (
            $_SERVER['OPENAI_IMAGE_MODEL']
            ?? getenv('OPENAI_IMAGE_MODEL')
            ?? $_ENV['OPENAI_IMAGE_MODEL']
            ?? 'dall-e-3'
        );
        $this->hfApiKey = (string) (
            $_SERVER['HUGGINGFACE_API_KEY']
            ?? getenv('HUGGINGFACE_API_KEY')
            ?? $_ENV['HUGGINGFACE_API_KEY']
            ?? ''
        );
    }

    public function generateAndSaveImage(string $description): array
    {
        $cleanDescription = trim((string) preg_replace('/\s+/', ' ', $description));
        $prompt = $this->buildPhotorealisticPrompt($cleanDescription);

        $imageData = $this->generateImageBinary($prompt, $cleanDescription);
        $saved = $this->saveImageToPublicFolder($imageData['binary'], $imageData['mime']);

        return [
            'provider'     => $imageData['provider'],
            'external'     => $imageData['external_url'] ?? null,
            'local'        => $saved['local_path'],
            'preview'      => $saved['preview_url'],
            'preview_data' => 'data:' . $imageData['mime'] . ';base64,' . base64_encode($imageData['binary']),
            'downloaded'   => true,
            'is_fallback'  => $imageData['is_fallback'] ?? false,
        ];
    }

    private function generateImageBinary(string $prompt, string $originalDescription): array
    {
        // 1. Try OpenAI if key is set (and not over quota)
        if ($this->openAiApiKey !== '') {
            try {
                return $this->generateWithOpenAI($prompt);
            } catch (\Throwable $e) {
                // Fall through to next provider
            }
        }

        // 2. Try HuggingFace if key is set (free tier available at huggingface.co)
        if ($this->hfApiKey !== '') {
            try {
                return $this->generateWithHuggingFace($prompt);
            } catch (\Throwable $e) {
                // Fall through to next provider
            }
        }

        // 3. Try Pollinations (free, no key needed) - multiple endpoints
        try {
            return $this->generateWithPollinations($prompt);
        } catch (\Throwable $e) {
            // Fall through to local fallback
        }

        // 4. Local SVG fallback (last resort)
        return $this->generateLocalFallbackImage($originalDescription);
    }

    private function generateWithOpenAI(string $prompt): array
    {
        $isDalle3 = str_contains($this->openAiModel, 'dall-e-3') || str_contains($this->openAiModel, 'gpt-image');

        $payload = json_encode([
            'model'           => $isDalle3 ? 'dall-e-3' : $this->openAiModel,
            'prompt'          => $prompt,
            'n'               => 1,
            'size'            => '1024x1024',
            'response_format' => 'url',
        ], JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            throw new \RuntimeException('Impossible de construire la requete IA.');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/images/generations');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->openAiApiKey,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Réduit de 120 à 30s
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $raw      = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false || $httpCode < 200 || $httpCode >= 300) {
            $detail = null;
            if (is_string($raw) && $raw !== '') {
                $jsonError = json_decode($raw, true);
                $detail    = $jsonError['error']['message'] ?? null;
            }
            $message = 'Echec appel OpenAI (HTTP ' . $httpCode . ').';
            if (is_string($detail) && $detail !== '') {
                $message .= ' Detail: ' . $detail;
            }
            throw new \RuntimeException($message);
        }

        $json = json_decode($raw, true);

        $imageUrl = $json['data'][0]['url'] ?? null;
        if (is_string($imageUrl) && $imageUrl !== '') {
            $binary = $this->downloadImageFromUrl($imageUrl);
            return [
                'binary'       => $binary,
                'mime'         => 'image/png',
                'provider'     => 'openai',
                'external_url' => $imageUrl,
                'is_fallback'  => false,
            ];
        }

        $base64 = $json['data'][0]['b64_json'] ?? null;
        if (is_string($base64) && $base64 !== '') {
            $binary = base64_decode($base64, true);
            if ($binary === false) {
                throw new \RuntimeException('Image OpenAI invalide (base64).');
            }
            return [
                'binary'       => $binary,
                'mime'         => 'image/png',
                'provider'     => 'openai',
                'external_url' => null,
                'is_fallback'  => false,
            ];
        }

        throw new \RuntimeException('OpenAI n a pas renvoye d image valide.');
    }

    /**
     * HuggingFace Inference API - gratuit avec compte HF
     * Créer un compte sur huggingface.co et générer un token dans Settings > Access Tokens
     * Ajouter HUGGINGFACE_API_KEY=hf_xxxxx dans .env.local
     */
    private function generateWithHuggingFace(string $prompt): array
    {
        // Nouveaux modèles plus performants et compatibles avec le nouveau router
        $models = [
            'black-forest-labs/FLUX.1-schnell',
            'stabilityai/stable-diffusion-xl-base-1.0',
            'runwayml/stable-diffusion-v1-5',
        ];

        $payload = json_encode(['inputs' => $prompt], JSON_UNESCAPED_UNICODE);

        foreach ($models as $model) {
            try {
                $ch = curl_init();
                // Utilisation du nouveau router HuggingFace (obligatoire en 2026)
                $url = "https://router.huggingface.co/hf-inference/models/{$model}";
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->hfApiKey,
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Réduit de 120 à 30s
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                $binary   = curl_exec($ch);
                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($binary === false || $httpCode < 200 || $httpCode >= 300) {
                    // Si c'est un message JSON, c'est peut-être que le modèle charge
                    if ($binary && str_contains($binary, '{')) {
                        $json = json_decode($binary, true);
                        if (isset($json['estimated_time'])) {
                            sleep(min((int)$json['estimated_time'], 20));
                            // Réessayer une fois
                            $ch2 = curl_init($url);
                            curl_setopt($ch2, CURLOPT_POST, true);
                            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch2, CURLOPT_HTTPHEADER, [
                                'Content-Type: application/json',
                                'Authorization: Bearer ' . $this->hfApiKey,
                            ]);
                            curl_setopt($ch2, CURLOPT_POSTFIELDS, $payload);
                            curl_setopt($ch2, CURLOPT_TIMEOUT, 60);
                            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
                            $binary = curl_exec($ch2);
                            $httpCode = (int) curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                            curl_close($ch2);
                            
                            if ($httpCode !== 200) continue;
                        } else {
                            continue;
                        }
                    } else {
                        continue;
                    }
                }

                // Verify it's actually an image
                $isImage = (
                    str_starts_with((string)$binary, "\xFF\xD8\xFF")  // JPEG
                    || str_starts_with((string)$binary, "\x89PNG")    // PNG
                    || str_starts_with((string)$binary, "RIFF")       // WebP
                );

                if (!$isImage) {
                    continue;
                }

                $mime = $this->detectMimeFromBinary((string)$binary);
                return [
                    'binary'       => (string)$binary,
                    'mime'         => $mime,
                    'provider'     => 'huggingface',
                    'external_url' => null,
                    'is_fallback'  => false,
                ];
            } catch (\Throwable $e) {
                continue;
            }
        }

        throw new \RuntimeException('HuggingFace indisponible (Router 2026).');
    }

    private function downloadImageFromUrl(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $binary   = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($binary === false || $httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException('Impossible de telecharger l image (HTTP ' . $httpCode . ').');
        }

        return (string) $binary;
    }

    private function generateWithPollinations(string $prompt): array
    {
        $seed    = random_int(1, 999999);
        $encoded = rawurlencode($prompt);

        // Try multiple Pollinations endpoints with different parameters
        $urls = [
            "https://image.pollinations.ai/prompt/{$encoded}?width=512&height=512&nologo=true&seed={$seed}&model=flux",
            "https://image.pollinations.ai/prompt/{$encoded}?width=512&height=512&nologo=true&seed={$seed}&model=turbo",
            "https://image.pollinations.ai/prompt/{$encoded}?width=512&height=512&nologo=true&seed={$seed}",
            "https://image.pollinations.ai/prompt/{$encoded}?width=256&height=256&nologo=true&seed={$seed}",
        ];

        foreach ($urls as $externalUrl) {
            try {
                $binary = $this->fetchImageFromUrl($externalUrl);
                if ($binary !== null) {
                    $mime = $this->detectMimeFromBinary($binary);
                    return [
                        'binary'       => $binary,
                        'mime'         => $mime,
                        'provider'     => 'pollinations',
                        'external_url' => $externalUrl,
                        'is_fallback'  => false,
                    ];
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        throw new \RuntimeException('Pollinations indisponible. Ajoutez HUGGINGFACE_API_KEY dans .env.local ou reessayez plus tard.');
    }

    private function fetchImageFromUrl(string $url): ?string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

        $binary      = curl_exec($ch);
        $httpCode    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($binary === false || $httpCode < 200 || $httpCode >= 300) {
            return null;
        }

        // Verify it's actually an image
        $isImage = (
            str_contains(strtolower($contentType), 'image/')
            || str_starts_with((string)$binary, "\xFF\xD8\xFF")  // JPEG
            || str_starts_with((string)$binary, "\x89PNG")        // PNG
            || str_starts_with((string)$binary, "RIFF")           // WebP
            || str_starts_with((string)$binary, "GIF8")           // GIF
        );

        if (!$isImage) {
            return null;
        }

        return (string) $binary;
    }

    private function detectMimeFromBinary(string $binary): string
    {
        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }
        if (str_starts_with($binary, "\x89PNG")) {
            return 'image/png';
        }
        if (str_starts_with($binary, "RIFF")) {
            return 'image/webp';
        }
        if (str_starts_with($binary, "GIF8")) {
            return 'image/gif';
        }
        return 'image/jpeg';
    }

    private function saveImageToPublicFolder(string $binary, string $mime): array
    {
        $filesystem = new Filesystem();
        $targetDir  = $this->projectDir . '/public/uploads/ai_images';
        if (!$filesystem->exists($targetDir)) {
            $filesystem->mkdir($targetDir, 0777);
        }

        $extension    = $this->extensionFromMime($mime);
        $name         = 'ai_' . time() . '_' . random_int(1000, 999999) . '.' . $extension;
        $absolutePath = $targetDir . '/' . $name;
        $filesystem->dumpFile($absolutePath, $binary);

        $localPath = '/uploads/ai_images/' . $name;
        return [
            'local_path'  => $localPath,
            'preview_url' => $localPath . '?v=' . time(),
        ];
    }

    private function buildPhotorealisticPrompt(string $description): string
    {
        return "Photo realiste d'objet recycle, style photographie produit, "
            . "lumiere naturelle, details nets, textures reelles, cadrage propre, "
            . "sans dessin, sans illustration, sans cartoon, sans texte, sans logo, sans watermark. "
            . "Sujet: {$description}";
    }

    private function extensionFromMime(string $mime): string
    {
        return match ($mime) {
            'image/png'     => 'png',
            'image/webp'    => 'webp',
            'image/gif'     => 'gif',
            'image/svg+xml' => 'svg',
            default         => 'jpg',
        };
    }

    private function generateLocalFallbackImage(string $description): array
    {
        $safeDesc = htmlspecialchars(substr($description, 0, 120), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $svg      = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#f3f4f6"/>
      <stop offset="100%" stop-color="#e5e7eb"/>
    </linearGradient>
  </defs>
  <rect width="512" height="512" fill="url(#g)"/>
  <rect x="36" y="36" width="440" height="440" rx="14" fill="#ffffff" stroke="#8b5e4a" stroke-width="3"/>
  <text x="256" y="160" text-anchor="middle" font-family="Arial, sans-serif" font-size="22" fill="#8b5e4a">Image IA (mode secours)</text>
  <text x="256" y="200" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" fill="#374151">Le service externe est indisponible.</text>
  <foreignObject x="60" y="230" width="392" height="200">
    <div xmlns="http://www.w3.org/1999/xhtml" style="font-family:Arial,sans-serif;font-size:14px;color:#111827;line-height:1.45;text-align:center;">
      {$safeDesc}
    </div>
  </foreignObject>
</svg>
SVG;

        return [
            'binary'       => $svg,
            'mime'         => 'image/svg+xml',
            'provider'     => 'local_fallback',
            'external_url' => null,
            'is_fallback'  => true,
        ];
    }
}
