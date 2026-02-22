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
            $disciplines = [
                'Sculpture'      => ['prix' => [80, 150], 'cap' => [5, 8],   'r' => 'La sculpture demande un matériel spécifique et un suivi individuel serré.'],
                'Céramique'      => ['prix' => [45, 85],  'cap' => [8, 12],  'r' => 'Atelier technique nécessitant l\'accès au four et au tour pour chaque participant.'],
                'Peinture'       => ['prix' => [30, 65],  'cap' => [12, 20], 'r' => 'Un atelier créatif convivial ouvert à un plus large public.'],
                'Artisanat'      => ['prix' => [40, 75],  'cap' => [10, 15], 'r' => 'Découverte de savoir-faire traditionnels avec petit groupe pour assurer la transmission.'],
                'Décoration'     => ['prix' => [35, 70],  'cap' => [10, 15], 'r' => 'Atelier de création d\'objets décoratifs, idéal pour des groupes moyens.'],
                'Mix artistique' => ['prix' => [50, 95],  'cap' => [8, 15],  'r' => 'Fusion des disciplines demandant une préparation et des matériaux variés.'],
            ];

            $found = 'Artisanat';
            foreach ($disciplines as $d => $config) {
                if (stripos($prompt, $d) !== false) {
                    $found = $d;
                    break;
                }
            }

            $conf = $disciplines[$found];
            // Utilisation d'un "seed" basé sur la longueur du prompt pour avoir une variation
            $seed = strlen($prompt);
            $prix = $conf['prix'][0] + ($seed % ($conf['prix'][1] - $conf['prix'][0]));
            $cap  = $conf['cap'][0] + ($seed % ($conf['cap'][1] - $conf['cap'][0]));
            
            return sprintf(
                '{"prix": %d, "capacite": %d, "raison": "%s"}',
                $prix, $cap, $conf['r']
            );
        }

        // Cas 2 : Description Magic (Copilote)
        if (str_contains($prompt, 'copilote') || str_contains($prompt, 'copywriter')) {
            $keywords = ['poterie', 'peinture', 'tissage', 'céramique', 'cuir', 'bijoux', 'sculpture'];
            $found = "artisanale";
            foreach ($keywords as $k) {
                if (stripos($prompt, $k) !== false) { $found = $k; break; }
            }

            $fallbacks = [
                "Plongez dans un univers où la matière rencontre l'esprit. Cet événement dédié à la $found vous invite à explorer des techniques ancestrales revisitées par une vision moderne. Une expérience immersive unique pour éveiller vos sens et votre créativité.",
                "Découvrez l'essence même du geste artistique à travers cet atelier de $found. Entre tradition et innovation, nous vous proposons un voyage au cœur de la création pure, où chaque détail raconte une histoire de passion et d'excellence.",
                "Une rencontre exceptionnelle avec l'art de la $found. Venez partager un moment privilégié au plus près de la création, pour comprendre les secrets d'un savoir-faire d'exception et repartir avec une source d'inspiration intarissable."
            ];

            $seed = strlen($prompt) % count($fallbacks);
            return $fallbacks[$seed];
        }

        // Cas 3 : Chat général (Client/Concierge)
        $userMsg = strtolower(substr($prompt, strrpos($prompt, "Client :") + 9));
        
        // 1. Salutations
        if (preg_match('/\b(bonjour|salut|hello|hi|hey|bonsoir)\b/u', $userMsg)) {
            $hellos = ["Bonjour ! Je suis votre Concierge AfkArt. Comment puis-je vous guider dans votre parcours culturel aujourd'hui ?", "Bonjour ! Ravi de vous voir. Souhaitez-vous découvrir nos derniers ateliers ou préférez-vous un conseil sur une discipline précise ?", "Hello ! Bienvenue chez AfkArt. Je suis là pour vous aider à trouver l'expérience artistique parfaite."];
            return $hellos[strlen($userMsg) % count($hellos)];
        }

        // 2. Recherche d'événements dans le contexte (le prompt contient déjà la liste des events)
        $categories = ['céramique', 'peinture', 'sculpture', 'artisanat', 'décoration', 'tissage', 'cuir', 'bijou'];
        foreach ($categories as $cat) {
            if (str_contains($userMsg, $cat)) {
                // On cherche si un événement de cette catégorie est mentionné dans le contexte (la liste envoyée au début du prompt)
                if (preg_match('/- (.*?) \('.preg_quote($cat, '/').'\) à (.*?)\. Prix: (.*?) TND/ui', $prompt, $m)) {
                    return "Excellente idée ! Pour la $cat, je vous recommande particulièrement l'événement **\"{$m[1]}\"** à {$m[2]}. Il est proposé à {$m[3]} TND. C'est une expérience très appréciée par notre communauté.";
                }
                return "L'art de la $cat est au cœur de l'esprit AfkArt. Nous avons régulièrement des ateliers dédiés. N'hésitez pas à consulter notre section 'Événements' pour voir les prochaines sessions disponibles !";
            }
        }

        // 3. Questions sur les prix / Tarifs (Précision sur 'combien' pour éviter conflit avec quantité)
        if (preg_match('/\b(prix|tarif|coûte|payant)\b/u', $userMsg) || (str_contains($userMsg, 'combien') && str_contains($userMsg, 'coût'))) {
            return "Nos événements sont accessibles à tous les budgets, allant généralement de 25 TND pour les initiations à 150 TND pour les masterclasses de sculpture. Quel budget aviez-vous en tête ?";
        }

        // 4. Aide générale / Liste / Quantité
        if (preg_match('/\b(liste|programme|quoi|faire|aide|conseil|combien|nombre|disponible)\b/u', $userMsg)) {
            // Compter les événements dans le contexte
            $count = preg_match_all('/- (.*?) \(/', $prompt, $matches);
            
            if ($count > 0) {
                if ($count === 1) {
                    return "Nous avons actuellement **un événement** de qualité supérieure : **\"{$matches[1][0]}\"**. Souhaitez-vous connaître les détails de cet atelier ?";
                }
                return "Nous avons actuellement **$count événements** passionnants programmés ! Vous pourriez commencer par découvrir l'atelier **\"{$matches[1][0]}\"**. Voulez-vous que je vous détaille les autres ?";
            }
            return "Il n'y a pas d'événement programmé pour le moment, mais revenez vite : nos artisans préparent de nouvelles pépites !";
        }

        return "Je suis à votre écoute. Souhaitez-vous des détails sur un événement particulier, ou cherchez-vous une recommandation basée sur vos goûts artistiques ?";
    }
}
