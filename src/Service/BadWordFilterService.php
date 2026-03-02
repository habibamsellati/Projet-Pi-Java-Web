<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class BadWordFilterService
{
    private HttpClientInterface $httpClient;
    private array $customBadWords;
    private array $whitelistedWords; // Mots autorisés même si l'API les détecte

    public function __construct(HttpClientInterface $httpClient, array $customBadWords = [], array $whitelistedWords = [])
    {
        $this->httpClient = $httpClient;
        $this->customBadWords = $customBadWords;
        $this->whitelistedWords = $whitelistedWords;
    }

    /**
     * Check if text contains bad words using custom list only
     * API disabled to avoid false positives
     * 
     * @param string $text The text to check
     * @return array ['hasBadWords' => bool, 'filteredText' => string, 'source' => string]
     */
    public function checkBadWords(string $text): array
    {
        if (empty(trim($text))) {
            return ['hasBadWords' => false, 'filteredText' => $text, 'source' => 'none'];
        }

        // Only check custom bad words list (API disabled)
        return $this->checkCustomBadWords($text);
    }

    /**
     * Check if text contains only whitelisted words
     * 
     * @param string $text The text to check
     * @return bool
     */

    /**
     * Check if text contains custom bad words
     * 
     * @param string $text The text to check
     * @return array ['hasBadWords' => bool, 'filteredText' => string, 'source' => string]
     */
    private function checkCustomBadWords(string $text): array
    {
        if (empty($this->customBadWords)) {
            return ['hasBadWords' => false, 'filteredText' => $text, 'source' => 'none'];
        }

        $lowerText = mb_strtolower($text);
        
        foreach ($this->customBadWords as $badWord) {
            $lowerBadWord = mb_strtolower(trim($badWord));
            if (empty($lowerBadWord)) {
                continue;
            }
            
            // Check if the bad word exists (as whole word OR as part of a word)
            // This will catch "lele" in "leleee", "lele123", etc.
            if (strpos($lowerText, $lowerBadWord) !== false) {
                return [
                    'hasBadWords' => true,
                    'filteredText' => $text,
                    'source' => 'custom',
                ];
            }
        }

        return ['hasBadWords' => false, 'filteredText' => $text, 'source' => 'none'];
    }

    /**
     * Get filtered text with bad words replaced by asterisks
     * 
     * @param string $text The text to filter
     * @return string Filtered text
     */
    public function getFilteredText(string $text): string
    {
        if (empty(trim($text))) {
            return $text;
        }

        // Filter custom bad words first
        $filteredText = $this->filterCustomBadWords($text);

        // Then filter with API
        try {
            $response = $this->httpClient->request('GET', 'https://www.purgomalum.com/service/plain', [
                'query' => [
                    'text' => $filteredText,
                ],
                'timeout' => 5,
            ]);

            return $response->getContent();
        } catch (\Exception $e) {
            return $filteredText;
        }
    }

    /**
     * Filter custom bad words by replacing them with asterisks
     * 
     * @param string $text The text to filter
     * @return string Filtered text
     */
    private function filterCustomBadWords(string $text): string
    {
        if (empty($this->customBadWords)) {
            return $text;
        }

        $filteredText = $text;
        
        foreach ($this->customBadWords as $badWord) {
            $badWord = trim($badWord);
            if (empty($badWord)) {
                continue;
            }
            
            // Replace bad word with asterisks (case-insensitive, including partial matches)
            $replacement = str_repeat('*', mb_strlen($badWord));
            $filteredText = str_ireplace($badWord, $replacement, $filteredText);
        }

        return $filteredText;
    }

    /**
     * Add a custom bad word to the list
     * 
     * @param string $word The word to add
     */
    public function addCustomBadWord(string $word): void
    {
        $word = trim($word);
        if (!empty($word) && !in_array($word, $this->customBadWords, true)) {
            $this->customBadWords[] = $word;
        }
    }

    /**
     * Get the list of custom bad words
     * 
     * @return array
     */
    public function getCustomBadWords(): array
    {
        return $this->customBadWords;
    }
}
