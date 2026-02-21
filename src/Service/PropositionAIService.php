<?php

namespace App\Service;

use App\Entity\Produit;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use App\Repository\PropositionRepository;

/**
 * PropositionAIService — IA pour les Propositions
 *
 * 1. Estimation du prix d'une œuvre d'art basée sur un produit recyclé
 * 2. Recommandation des meilleurs artisans pour un produit donné
 */
class PropositionAIService
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly UserRepository $userRepository,
        private readonly PropositionRepository $propositionRepository,
    ) {}

    // ─────────────────────────────────────────
    //  1. ESTIMATION DU PRIX
    // ─────────────────────────────────────────

    /**
     * Prix de base par catégorie de matériau (en TND).
     * Reflète la complexité du travail artisanal pour chaque matériau.
     */
    private const BASE_PRICES = [
        'Bois'      => ['min' => 45,  'max' => 250, 'avg' => 120],
        'Métal'     => ['min' => 55,  'max' => 350, 'avg' => 160],
        'Verre'     => ['min' => 40,  'max' => 280, 'avg' => 130],
        'Tissu'     => ['min' => 25,  'max' => 180, 'avg' => 80],
        'Plastique' => ['min' => 15,  'max' => 120, 'avg' => 55],
        'Papier'    => ['min' => 10,  'max' => 90,  'avg' => 40],
        'Carton'    => ['min' => 12,  'max' => 100, 'avg' => 45],
    ];

    /**
     * Multiplicateurs selon l'état du produit.
     * Un produit en bon état a plus de valeur potentielle.
     */
    private const STATE_MULTIPLIERS = [
        'Bon'     => 1.3,
        'Moyen'   => 1.0,
        'Mauvais' => 0.7,
    ];

    /**
     * Multiplicateurs selon le type de matériau.
     * Les matériaux écologiques/durables ont plus de valeur sur le marché artisanal.
     */
    private const TYPE_MULTIPLIERS = [
        'Naturel'      => 1.2,
        'Durable'      => 1.15,
        'Écologique'   => 1.25,
        'Zéro déchet'  => 1.1,
        'Réutilisable' => 1.05,
    ];

    /**
     * Estime le prix d'une œuvre à partir d'un produit recyclé.
     *
     * @return array{
     *   min: float,
     *   max: float,
     *   estimated: float,
     *   confidence: float,
     *   breakdown: array,
     *   market_avg: float|null
     * }
     */
    public function estimatePrice(Produit $produit): array
    {
        $category = $produit->getNomproduit() ?? 'Papier';
        $state    = $produit->getEtat() ?? 'Moyen';
        $type     = $produit->getTypemateriau() ?? 'Réutilisable';
        $quantity = $produit->getQuantite() ?? 1;

        // Prix de base
        $base = self::BASE_PRICES[$category] ?? self::BASE_PRICES['Papier'];

        // Multiplicateur état
        $stateMultiplier = self::STATE_MULTIPLIERS[$state] ?? 1.0;

        // Multiplicateur type
        $typeMultiplier = self::TYPE_MULTIPLIERS[$type] ?? 1.0;

        // Multiplicateur quantité (prix augmente mais pas linéairement)
        $qtyMultiplier = 1.0 + (log(max($quantity, 1)) * 0.1);

        // Calcul final
        $totalMultiplier = $stateMultiplier * $typeMultiplier * $qtyMultiplier;
        $estimatedMin = round($base['min'] * $totalMultiplier, 2);
        $estimatedMax = round($base['max'] * $totalMultiplier, 2);
        $estimated    = round($base['avg'] * $totalMultiplier, 2);

        // Chercher le prix moyen réel dans la base d'articles existants
        $marketAvg = $this->getMarketAverage($category);
        $confidence = 65.0; // Confiance de base

        // Si on a des données de marché, ajuster l'estimation et la confiance
        if ($marketAvg !== null) {
            $estimated = round(($estimated * 0.6) + ($marketAvg * 0.4), 2);
            $confidence = min(90.0, $confidence + 20.0);
        }

        return [
            'min'        => $estimatedMin,
            'max'        => $estimatedMax,
            'estimated'  => $estimated,
            'confidence' => $confidence,
            'currency'   => 'TND',
            'breakdown'  => [
                'base_price'      => $base['avg'],
                'state_multiplier' => $stateMultiplier,
                'type_multiplier'  => $typeMultiplier,
                'qty_multiplier'   => round($qtyMultiplier, 2),
                'category'         => $category,
                'state'            => $state,
                'type'             => $type,
            ],
            'market_avg' => $marketAvg,
        ];
    }

    /**
     * Estime le prix à partir de paramètres bruts (sans entité Produit).
     */
    public function estimatePriceFromParams(string $category, string $state, string $type, int $quantity = 1): array
    {
        $produit = new Produit();
        $produit->setNomproduit($category);
        $produit->setEtat($state);
        $produit->setTypemateriau($type);
        $produit->setQuantite($quantity);

        return $this->estimatePrice($produit);
    }

    /**
     * Calcule le prix moyen du marché pour une catégorie (basé sur les articles existants).
     */
    private function getMarketAverage(string $category): ?float
    {
        try {
            $articles = $this->articleRepository->searchWithFilters(null, $category, null);
            if (empty($articles)) {
                return null;
            }

            $prices = array_filter(
                array_map(fn($a) => $a->getPrix() !== null ? (float) $a->getPrix() : null, $articles),
                fn($p) => $p !== null && $p > 0
            );

            if (empty($prices)) {
                return null;
            }

            return round(array_sum($prices) / count($prices), 2);
        } catch (\Exception) {
            return null;
        }
    }

    // ─────────────────────────────────────────
    //  2. RECOMMANDATION D'ARTISANS
    // ─────────────────────────────────────────

    /**
     * Spécialités des artisans par catégorie de matériau.
     * Utilisé quand on n'a pas assez de données historiques.
     */
    private const ARTISAN_SPECIALTIES = [
        'Bois'      => ['menuiserie', 'ébénisterie', 'sculpture', 'marqueterie'],
        'Métal'     => ['ferronnerie', 'bijouterie', 'soudure', 'sculpture'],
        'Verre'     => ['vitrail', 'soufflage', 'mosaïque', 'bijouterie'],
        'Tissu'     => ['couture', 'tapisserie', 'broderie', 'patchwork'],
        'Plastique' => ['upcycling', 'design', 'moulage', 'impression 3d'],
        'Papier'    => ['origami', 'papier mâché', 'reliure', 'collage'],
        'Carton'    => ['modélisme', 'design', 'meuble carton', 'décoration'],
    ];

    /**
     * Recommande les meilleurs artisans pour un produit donné.
     *
     * Critères de scoring :
     * 1. Nombre d'articles créés dans la même catégorie (expérience)
     * 2. Prix moyen des articles (qualité/valeur)
     * 3. Nombre total d'articles (activité)
     *
     * @return array<int, array{
     *   id: int,
     *   nom: string,
     *   prenom: string,
     *   score: float,
     *   articles_count: int,
     *   category_count: int,
     *   avg_price: float|null,
     *   specialties: string[],
     *   match_reason: string
     * }>
     */
    public function recommendArtisans(Produit $produit, int $limit = 5): array
    {
        $category = $produit->getNomproduit() ?? '';

        // Récupérer tous les artisans actifs
        $artisans = $this->userRepository->createQueryBuilder('u')
            ->where('u.role = :role1 OR u.role = :role2')
            ->setParameter('role1', 'ARTISANT')
            ->setParameter('role2', 'ARTISAN')
            ->andWhere('u.deletedAt IS NULL')
            ->getQuery()
            ->getResult();

        if (empty($artisans)) {
            return [];
        }

        $recommendations = [];

        foreach ($artisans as $artisan) {
            $score = 0.0;
            $matchReasons = [];

            // SCORE UNIQUE : Activité & Succès sur les propositions
            // Basé uniquement sur le travail de terrain (propositions faites par l'artisan)
            $propStats = $this->propositionRepository->createQueryBuilder('p')
                ->select('COUNT(p.id) as total, SUM(CASE WHEN p.statut IN (\'acceptee\', \'terminee\') THEN 1 ELSE 0 END) as success')
                ->where('p.user = :artisan')
                ->setParameter('artisan', $artisan)
                ->getQuery()
                ->getSingleResult();

            $totalProps = (int) ($propStats['total'] ?? 0);
            $successProps = (int) ($propStats['success'] ?? 0);

            if ($totalProps > 0) {
                // 10 pts par proposition réussie + 2 pts par proposition envoyée
                $score += ($successProps * 10) + ($totalProps * 2);
                
                if ($successProps > 0) {
                    $matchReasons[] = sprintf('%d réalisation(s) réussie(s)', $successProps);
                } else {
                    $matchReasons[] = sprintf('%d proposition(s) envoyée(s)', $totalProps);
                }
            } else {
                // Pour les nouveaux qui n'ont pas encore de propositions
                $score += 5; 
                $matchReasons[] = 'Prêt pour son premier défi';
            }

            $specialties = self::ARTISAN_SPECIALTIES[$category] ?? [];

            $recommendations[] = [
                'id'             => $artisan->getId(),
                'nom'            => $artisan->getNom() ?? '',
                'prenom'         => $artisan->getPrenom() ?? '',
                'score'          => round($score, 1),
                'articles_count' => 0,
                'category_count' => 0,
                'avg_price'      => null,
                'specialties'    => $specialties,
                'match_reason'   => implode(' • ', array_unique($matchReasons)),
            ];
        }

        // Trier par score décroissant
        usort($recommendations, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($recommendations, 0, $limit);
    }

    /**
     * Recommande les artisans à partir de paramètres bruts.
     */
    public function recommendArtisansFromParams(string $category, int $limit = 5): array
    {
        $produit = new Produit();
        $produit->setNomproduit($category);

        return $this->recommendArtisans($produit, $limit);
    }
}
