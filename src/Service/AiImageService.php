<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class AiImageService
{
    /**
     * Simulates AI image generation by fetching high-quality stock photos
     * based on event keywords.
     */
    private string $projectDir;

    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        string $projectDir
    ) {
        $this->projectDir = $projectDir;
    }

    /**
     * Simulates AI image generation by fetching high-quality stock photos
     */
    private array $artPhotoIds = [
        'photo-1513364776144-60967b0f800f', // Gallery
        'photo-1541963463532-d68292c34b19', // Art books/paint
        'photo-1460661419201-fd4cecdf8a8b', // Art supplies
        'photo-1579783902614-a3fb3927b6a5', // Painting portrait
        'photo-1579783900882-c0d3dad7b119', // Colorful palette
        'photo-1578301978693-85fa9c0320b9', // Contemporary gallery
        'photo-1582555172866-f73bb12a2ab3', // Museum hall
        'photo-1577083552431-6e5fd01aa342', // Abstract art
        'photo-1576133600371-33f7cc91e98d', // Studio
        'photo-1536924940846-227afb31e2a5'  // Texture
    ];

    public function generateForEvent(string $name, string $description): string
    {
        $id = $this->artPhotoIds[array_rand($this->artPhotoIds)];
        return "https://images.unsplash.com/{$id}?auto=format&fit=crop&q=80&w=1920";
    }

    /**
     * Generates 5 realistic artistic images with specific gallery perspectives
     */
    public function generateGalleryData(string $name, string $description): array
    {
        $baseKeywords = $this->extractKeywords($name . ' ' . $description);
        $searchTerms = implode(',', $baseKeywords);
        
        $views = [
            'Vue d\'Ensemble (Collection)' => ['exhibition', 'gallery', 'display'],
            'Focus Texture (Détail)' => ['macro', 'texture', 'detail'],
            'Ambiance Vernissage' => ['social', 'event', 'people', 'opening'],
            'Angle d\'Exposition' => ['lighting', 'museum', 'arrangement'],
            'Studio Créatif (Backstage)' => ['studio', 'workshop', 'artist', 'making']
        ];

        $galleryData = [];
        foreach ($views as $caption => $specifics) {
            // Join base keywords and specific terms with literal commas
            $allKeywords = array_unique(array_merge($baseKeywords, $specifics));
            $queryString = implode(',', $allKeywords);

            // Use direct Unsplash Source URL. Don't encode the entire string as commas are delimiters.
            // Using sig to bypass caching and ensure variety.
            $url = "https://source.unsplash.com/featured/1600x900/?" . $queryString . "&sig=" . uniqid();
            
            $galleryData[] = ['url' => $url, 'caption' => $caption];
        }

        return $galleryData;
    }

    /**
     * Downloads images to public/uploads/events/[id]/
     */
    public function storeImagesLocally(int $eventId, array $imagesData): array
    {
        $uploadDir = $this->projectDir . "/public/uploads/events/{$eventId}";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $storedPaths = [];
        $timestamp = time();
        foreach ($imagesData as $index => $data) {
            $url = $data['url'];
            $filename = "visuel_" . ($index + 1) . "_{$timestamp}.jpg";
            $targetPath = $uploadDir . "/" . $filename;
            
            $success = false;
            
            // 1. Try with cURL (more robust for SSL/Redirects)
            if (function_exists('curl_version')) {
                $ch = curl_init($url);
                $fp = fopen($targetPath, 'wb');
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                $success = curl_exec($ch);
                curl_close($ch);
                fclose($fp);
            }

            // 2. Fallback to file_get_contents if cURL failed or isn't available
            if (!$success) {
                $context = stream_context_create([
                    "ssl" => ["verify_peer" => false, "verify_peer_name" => false],
                    "http" => ["header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n", "follow_location" => 1]
                ]);
                $content = @file_get_contents($url, false, $context);
                if ($content !== false) {
                    $success = (bool) file_put_contents($targetPath, $content);
                }
            }
            
            if ($success && file_exists($targetPath)) {
                $storedPaths[] = [
                    'url' => "/uploads/events/{$eventId}/{$filename}",
                    'caption' => $data['caption']
                ];
            } else {
                // Final fallback if both local storage attempts fail
                // Use loremflickr which is very stable for keyword-based placeholders
                $fallbackKeywords = implode(',', array_slice($allKeywords ?? ['art'], 0, 2));
                $storedPaths[] = [
                    'url' => "https://loremflickr.com/1600/900/{$fallbackKeywords}/all?sig=" . uniqid(),
                    'caption' => $data['caption'] . " (Live)"
                ];
            }
        }

        return $storedPaths;
    }

    private function extractKeywords(string $text): array
    {
        $text = strtolower(strip_tags($text));
        
        // Define known art categories to look for
        $categories = [
            'poterie' => 'pottery',
            'pottery' => 'pottery',
            'céramique' => 'ceramics',
            'ceramics' => 'ceramics',
            'clay' => 'clay',
            'peinture' => 'painting',
            'sculpture' => 'sculpture',
            'bijoux' => 'jewelry',
            'artisan' => 'handicraft',
            'tissage' => 'weaving',
            'bois' => 'woodwork',
            'métal' => 'metalwork'
        ];

        $detectedCategories = [];
        foreach ($categories as $fr => $en) {
            if (str_contains($text, $fr) || str_contains($text, $en)) {
                $detectedCategories[] = $en;
            }
        }

        // Standard keyword extraction for unique terms
        $words = str_word_count($text, 1);
        $stopWords = ['the', 'and', 'with', 'for', 'this', 'that', 'evenement', 'événement', 'notre', 'votre', 'dans', 'pour', 'avec', 'plus', 'être', 'faire', 'fait', 'tous', 'ceux', 'tunisie', 'tunisia', 'authentique', 'atelier'];
        
        $filtered = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 4 && !in_array($word, $stopWords);
        });
        
        $mainKeywords = array_slice(array_unique($filtered), 0, 3);
        
        // Build the query: Detected Category (if any) + Top Keywords + 'artistic'
        $finalKeywords = array_unique(array_merge($detectedCategories, $mainKeywords));
        
        if (empty($finalKeywords)) {
            $finalKeywords = ['art', 'artistic'];
        }

        return $finalKeywords;
    }
}
