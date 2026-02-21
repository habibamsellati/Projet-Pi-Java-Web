<?php

namespace App\Service;

use App\Repository\EvenementRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HuggingFaceService
{
    private HttpClientInterface $httpClient;
    private EvenementRepository $evenementRepository;
    private string $apiToken;
    private string $modelUrl;

    public function __construct(
        HttpClientInterface $httpClient,
        EvenementRepository $evenementRepository
    ) {
        $this->httpClient = $httpClient;
        $this->evenementRepository = $evenementRepository;
        $this->apiToken = $_ENV['HUGGINGFACE_API_TOKEN'] ?? '';
        // Using Mistral-7B-Instruct-v0.3 as a powerful open-source choice
        $this->modelUrl = 'https://api-inference.huggingface.co/models/mistralai/Mistral-7B-Instruct-v0.3';
    }

    public function getChatResponse(string $userMessage): string
    {
        $events = $this->evenementRepository->findAll();
        $eventContext = "";
        foreach (array_slice($events, 0, 10) as $event) {
            $eventContext .= sprintf(
                "- %s (%s) à %s. Prix: %s TND.\n",
                $event->getNom(),
                $event->getTypeArt(),
                $event->getLieu(),
                $event->getPrix()
            );
        }

        $systemPrompt = "Tu es le 'Concierge AfkArt', un assistant culturel expert et élégant. 
        Missions : 1. Recommandations clients. 2. Aide créative artisans.
        Contexte événements :\n" . $eventContext . "\n
        Réponds en Français, ton raffiné.";

        return $this->getAiResponse($systemPrompt . "\n\nClient : " . $userMessage);
    }

    public function generateMagicDescription(string $title, string $typeArt, string $theme): string
    {
        $prompt = "Tu es un copywriter d'art. Rédige une description poétique et vendeuse pour cet événement AfkArt :\n" .
                  "Titre : $title\nDiscipline : $typeArt\nThème : $theme\n" .
                  "Réponds directemet par la description (3-4 phrases). Pas d'introduction.";
        
        return $this->getAiResponse($prompt);
    }

    public function suggestMagicPricing(string $typeArt, string $theme): array
    {
        $prompt = "Analyse le marché AfkArt. Suggère un prix (TND) et une jauge pour :\n" .
                  "Discipline : $typeArt\nThème : $theme\n" .
                  "Réponds UNIQUEMENT au format JSON : {\"prix\": XX, \"capacite\": XX, \"raison\": \"...\"}";
        
        $response = $this->getAiResponse($prompt);
        
        // Nettoyage si l'IA ajoute du texte autour du JSON
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $data = json_decode($matches[0], true);
            if ($data) return $data;
        }

        return ['prix' => 45, 'capacite' => 15, 'raison' => "Basé sur les standards de l'artisanat tunisien."];
    }

    private function getAiResponse(string $prompt): string
    {
        // MODE DÉMO INTELLIGENT : Si la clé est manquante, on simule une IA de haute qualité
        if (empty($this->apiToken) || $this->apiToken === 'hf_your_token_here') {
            return $this->generateDemoFallback($prompt);
        }

        try {
            $response = $this->httpClient->request('POST', $this->modelUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'inputs' => "<s>[INST] " . $prompt . " [/INST]",
                    'parameters' => [
                        'max_new_tokens' => 500,
                        'temperature' => 0.7,
                    ],
                ],
                'timeout' => 20,
            ]);

            $statusCode = $response->getStatusCode();
            $result = $response->toArray(false);

            if ($statusCode === 503) {
                return $this->generateDemoFallback($prompt) . " (Note: Modèle en chargement, affichage démo)";
            }

            if ($statusCode !== 200) {
                return $this->generateDemoFallback($prompt);
            }

            return trim($result[0]['generated_text'] ?? $this->generateDemoFallback($prompt));
        } catch (\Exception $e) {
            return $this->generateDemoFallback($prompt);
        }
    }

    /**
     * Simule une réponse d'IA de haute qualité pour la démo si l'API est indisponible
     */
    private function generateDemoFallback(string $prompt): string
    {
        // Cas 1 : Suggestion de prix (JSON)
        if (str_contains($prompt, 'JSON')) {
            return '{"prix": 55, "capacite": 12, "raison": "Basé sur les standards AfkArt pour garantir une expérience artisanale exclusive et équitable."}';
        }

        // Cas 2 : Description Magic (Copilote)
        if (str_contains($prompt, 'copilote') || str_contains($prompt, 'copywriter')) {
            $keywords = ['poterie', 'peinture', 'tissage', 'céramique', 'cuir', 'bijoux'];
            $found = "artisanale";
            foreach ($keywords as $k) {
                if (stripos($prompt, $k) !== false) { $found = $k; break; }
            }

            $fallbacks = [
                "Plongez dans un univers où la matière rencontre l'esprit. Cet événement dédié à la $found vous invite à explorer des techniques ancestrales revisitées par une vision moderne. Une expérience immersive unique pour éveiller vos sens et votre créativité.",
                "Découvrez l'essence même du geste artistique à travers cet atelier de $found. Entre tradition et innovation, nous vous proposons un voyage au cœur de la création pure, où chaque détail raconte une histoire de passion et d'excellence.",
                "Une rencontre exceptionnelle avec l'art de la $found. Venez partager un moment privilégié au plus près de la création, pour comprendre les secrets d'un savoir-faire d'exception et repartir avec une source d'inspiration intarissable."
            ];

            return $fallbacks[array_rand($fallbacks)];
        }

        // Cas 3 : Chat général (Client)
        return "Bienvenue chez AfkArt ! En tant que votre Concierge Culturel, je vous suggère d'explorer nos ateliers de céramique et nos expositions de peinture contemporaine. Avez-vous une préférence pour une discipline artistique particulière ?";
    }
}
