<?php

namespace App\Service;

use App\Entity\Evenement;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class SmartFillService
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    public function predictFillRate(Evenement $event): ?float
    {
        $data = [
            'capacity' => $event->getCapacite(),
            'historical_avg' => 45, // Simulation: could be calculated from previous events
            'type_art' => $event->getTypeArt(),
            'prix' => (float) $event->getPrix(),
            'days_left' => $event->getDateDebut() ? $event->getDateDebut()->diff(new \DateTime())->days : 30
        ];

        $process = new Process(['python', $this->projectDir . '/ML/predict.py', json_encode($data)]);
        
        try {
            $process->run();

            if (!$process->isSuccessful()) {
                // Fallback for demo if python is not configured
                return $this->fallbackPrediction($data);
            }

            $output = json_decode($process->getOutput(), true);
            return $output['prediction'] ?? null;

        } catch (\Exception $e) {
            return $this->fallbackPrediction($data);
        }
    }

    /**
     * Strategic Brief: AI-generated catchy marketing tags
     */
    public function generateStrategicBrief(Evenement $event, ?float $prediction): array
    {
        $briefs = [];
        if ($prediction > 75) $briefs[] = "Exposition Premium";
        if ($event->getPrix() < 30) $briefs[] = "Offre Accessible";
        if ($prediction < 40) $briefs[] = "Ajustement Nécessaire";
        if (strlen((string)$event->getDescription()) > 200) $briefs[] = "Contenu Riche";
        
        return array_slice($briefs, 0, 2);
    }

    /**
     * Success Score Calculation (0-100)
     * Factors: Base Prediction (60%), Price Competitiveness (20%), Content Quality (20%)
     */
    public function calculateSuccessScore(Evenement $event, ?float $prediction): int
    {
        if ($prediction === null) return 0;
        
        $score = $prediction * 0.6;
        
        // Price Factor (Assume 50 is ideal for the target market)
        $price = (float)$event->getPrix();
        if ($price < 40) $score += 20;
        elseif ($price < 80) $score += 15;
        else $score += 5;

        // Content Quality
        $descLen = strlen((string)$event->getDescription());
        if ($descLen > 300) $score += 20;
        elseif ($descLen > 100) $score += 10;
        
        return (int)min($score, 100);
    }

    /**
     * Price Elasticity Simulation: Predicts fill rate at different price points
     */
    public function calculatePriceElasticity(?float $currentPrediction, float $currentPrice): array
    {
        if ($currentPrediction === null) return [];

        return [
            'decrease_20' => min(100, $currentPrediction * 1.25),
            'decrease_10' => min(100, $currentPrediction * 1.12),
            'current' => $currentPrediction,
            'increase_10' => max(0, $currentPrediction * 0.90),
        ];
    }

    /**
     * Strategic Analysis: Breakdown of sub-scores
     */
    public function calculateStrategicAnalysis(Evenement $event, ?float $prediction): array
    {
        $price = (float)$event->getPrix();
        $descLen = strlen((string)$event->getDescription());
        
        // Price Score (Logic of Excellence: more sensitive to variations)
        if ($price < 25) $priceScore = 95;
        elseif ($price < 45) $priceScore = 85;
        elseif ($price < 75) $priceScore = 75;
        elseif ($price < 110) $priceScore = 60;
        else $priceScore = 40;

        // Content Score (0-100)
        if ($descLen > 400) $contentScore = 100;
        elseif ($descLen > 150) $contentScore = 80;
        elseif ($descLen > 50) $contentScore = 50;
        else $contentScore = 20;

        // Reach Score (Fixed variable name)
        $reachScore = min(100, (int)($event->getCapacite() / 2) + 20);

        return [
            'price_score' => $priceScore,
            'content_score' => $contentScore,
            'reach_score' => $reachScore,
            'timing_score' => 85,
        ];
    }

    /**
     * Strategic Checklist: Actionable steps for ALL pillars
     */
    public function getStrategicChecklist(Evenement $event, array $analysis): array
    {
        $checklist = [];
        
        // 1. Content Pillar
        if ($analysis['content_score'] < 50) {
            $checklist[] = ['task' => 'Urgent: Contenu trop pauvre', 'impact' => 'Critique', 'detail' => 'Votre description est trop courte. Pour rassurer les clients, décrivez l\'ambiance, le programme et ce qui rend cet événement unique.'];
        } elseif ($analysis['content_score'] < 85) {
            $checklist[] = ['task' => 'Peaufiner la présentation', 'impact' => 'Moyen', 'detail' => 'Utilisez des listes à puces pour rendre votre programme plus lisible.'];
        } else {
            $checklist[] = ['task' => 'Contenu Excellent', 'impact' => 'Succès', 'detail' => 'Votre description est riche. Pensez à mettre à jour les FAQ si les clients posent des questions.'];
        }
        
        // 2. Price Pillar
        if ($analysis['price_score'] < 60) {
            $checklist[] = ['task' => 'Prix au dessus du marché', 'impact' => 'Elevé', 'detail' => 'Votre tarif est supérieur à la moyenne. Justifiez-le par des services exclusifs ou proposez un tarif Early Bird.'];
        } elseif ($analysis['price_score'] > 90) {
            $checklist[] = ['task' => 'Prix très attractif', 'impact' => 'Succès', 'detail' => 'Votre prix est un aimant à clients. Ne le changez pas !'];
        } else {
            $checklist[] = ['task' => 'Ajustement tarifaire', 'impact' => 'Faible', 'detail' => 'Votre prix est correct. Testez une augmentation de 5% si le remplissage dépasse les 80%.'];
        }

        // 3. Media Pillar
        $imgCount = $event->getImages()->count();
        if ($imgCount < 3) {
            $checklist[] = ['task' => 'Manque de visuels', 'impact' => 'Critique', 'detail' => 'Un événement avec 3 à 5 photos de haute qualité augmente le taux de réservation de 40%.'];
        }

        // 4. Visibility Pillar
        if ($analysis['reach_score'] < 50) {
            $checklist[] = ['task' => 'Booster la visibilité', 'impact' => 'Elevé', 'detail' => 'Partagez cet événement sur vos réseaux sociaux pour augmenter la "Portée" calculée par l\'IA.'];
        }

        return $checklist;
    }

    public function getInsights(?float $prediction): array
    {
        if ($prediction === null) {
            return [
                'status' => 'secondary',
                'message' => 'Analyse indisponible',
                'action' => 'Ajoutez plus d\'événements pour activer l\'IA.'
            ];
        }

        if ($prediction > 80) {
            return [
                'status' => 'success',
                'message' => 'Succès Garanti ! 🔥',
                'action' => 'Forte demande prévue. Pensez à augmenter votre stock ou vos places.'
            ];
        } elseif ($prediction > 50) {
            return [
                'status' => 'warning',
                'message' => 'Potentiel Moyen 📈',
                'action' => 'Boostez votre visibilité sur les réseaux sociaux.'
            ];
        } else {
            return [
                'status' => 'danger',
                'message' => 'Attention : Faible affluence ⚠️',
                'action' => 'Revoyez votre prix ou proposez une offre spéciale.'
            ];
        }
    }

    private function fallbackPrediction(array $data): float
    {
        // Simple deterministic fallback logic consistent with the python script
        $base = 45;
        if ($data['prix'] < 30) $base += 10;
        if ($data['type_art'] === 'Céramique') $base *= 1.1;
        return min($base, 100);
    }
}
