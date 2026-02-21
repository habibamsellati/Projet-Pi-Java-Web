<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PersonalizedMessageService
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Generate a personalized order confirmation message using AI
     */
    public function generateOrderConfirmationMessage(
        string $customerName,
        array $articles,
        float $total,
        string $orderNumber
    ): string {
        // Build article list for the prompt
        $articleList = [];
        foreach ($articles as $article) {
            $articleList[] = $article->getTitre();
        }
        $articlesText = implode(', ', $articleList);

        // Create prompt for the AI
        $prompt = sprintf(
            "Écris un message de confirmation de commande personnalisé et chaleureux en français pour %s. " .
            "La commande numéro %s contient: %s. Le montant total est de %.2f€. " .
            "Le message doit être court (3-4 phrases), professionnel mais amical, et remercier le client. " .
            "Ne pas inclure de signature.",
            $customerName,
            $orderNumber,
            $articlesText,
            $total
        );

        try {
            // Try to generate with Hugging Face API (free tier)
            $response = $this->httpClient->request('POST', 'https://api-inference.huggingface.co/models/mistralai/Mistral-7B-Instruct-v0.2', [
                'json' => [
                    'inputs' => $prompt,
                    'parameters' => [
                        'max_new_tokens' => 150,
                        'temperature' => 0.7,
                        'top_p' => 0.9,
                    ]
                ],
                'timeout' => 10,
            ]);

            $data = $response->toArray();
            
            if (isset($data[0]['generated_text'])) {
                $generatedText = $data[0]['generated_text'];
                // Extract only the generated part (remove the prompt)
                $message = str_replace($prompt, '', $generatedText);
                $message = trim($message);
                
                if (!empty($message)) {
                    return $message;
                }
            }
        } catch (\Exception $e) {
            // If API fails, use fallback
        }

        // Fallback: Generate a nice message without AI
        return $this->generateFallbackMessage($customerName, $articles, $total, $orderNumber);
    }

    /**
     * Fallback message generator (template-based)
     */
    private function generateFallbackMessage(
        string $customerName,
        array $articles,
        float $total,
        string $orderNumber
    ): string {
        $templates = [
            "Bonjour %s,\n\nNous avons bien reçu votre commande n°%s d'un montant de %.2f€. " .
            "Votre sélection de %d article(s) sera traitée avec soin par notre équipe. " .
            "Merci de votre confiance et à très bientôt !",
            
            "Cher(e) %s,\n\nVotre commande n°%s est confirmée ! " .
            "Nous préparons avec attention vos %d article(s) pour un montant total de %.2f€. " .
            "Merci pour votre achat et à bientôt sur notre plateforme !",
            
            "Merci %s !\n\nVotre commande n°%s (%.2f€) a été enregistrée avec succès. " .
            "Nos équipes s'occupent dès maintenant de préparer vos %d article(s). " .
            "Nous apprécions votre confiance !",
        ];

        $template = $templates[array_rand($templates)];
        
        return sprintf(
            $template,
            $customerName,
            $orderNumber,
            $total,
            count($articles)
        );
    }
}
