<?php

namespace App\Service;

/**
 * NLPCategoryDetector — Détection automatique de catégorie produit par NLP
 *
 * Analyse un texte libre (ex: "j'ai une vieille chaise cassée en bois")
 * et détecte automatiquement :
 *   - catégorie (nomproduit) : Plastique, Papier, Carton, Verre, Métal, Bois, Tissu
 *   - type (typemateriau) : Naturel, Durable, Écologique, Zéro déchet, Réutilisable
 *   - état : Bon, Moyen, Mauvais
 *
 * Approche : dictionnaire de mots-clés pondérés + scoring TF-IDF simplifié
 */
class NLPCategoryDetector
{
    // ──────────────────────────────────────────────
    //  DICTIONNAIRE : catégorie matériau (nomproduit)
    // ──────────────────────────────────────────────
    private const CATEGORY_KEYWORDS = [
        'Bois' => [
            // Matériau direct
            'bois'       => 3.0, 'boisé'      => 2.0, 'boisée'     => 2.0,
            'chêne'      => 3.0, 'pin'        => 2.5, 'sapin'      => 3.0,
            'hêtre'      => 3.0, 'noyer'      => 3.0, 'merisier'   => 3.0,
            'teck'       => 3.0, 'bambou'     => 2.5, 'contreplaqué' => 3.0,
            'aggloméré'  => 2.5, 'mdf'        => 2.5, 'parquet'    => 2.5,
            'planche'    => 2.0, 'poutre'     => 2.5, 'rondin'     => 2.5,
            'sciure'     => 2.0, 'copeaux'    => 2.0,
            // Objets en bois
            'chaise'     => 1.5, 'table'      => 1.2, 'meuble'     => 1.5,
            'armoire'    => 1.5, 'étagère'    => 1.3, 'commode'    => 1.5,
            'bureau'     => 1.0, 'lit'        => 0.8, 'porte'      => 1.0,
            'fenêtre'    => 0.8, 'palette'    => 2.0, 'caisse'     => 1.0,
            'tonneau'    => 2.0, 'tabouret'   => 1.3, 'banc'       => 1.3,
            'coffre'     => 1.0, 'bibliothèque' => 1.3, 'placard'  => 1.2,
        ],
        'Plastique' => [
            'plastique'  => 3.0, 'plastic'    => 3.0, 'pvc'        => 3.0,
            'polyéthylène' => 3.0, 'polypropylène' => 3.0, 'polystyrène' => 3.0,
            'nylon'      => 2.5, 'acrylique'  => 2.5, 'résine'     => 2.0,
            'polymère'   => 2.5, 'pet'        => 2.5, 'hdpe'       => 3.0,
            'abs'        => 2.5, 'silicone'   => 2.0, 'cellophane' => 2.5,
            'polycarbonate' => 3.0, 'plexiglas' => 2.5,
            // Objets en plastique
            'bouteille'  => 2.0, 'sac'        => 1.5, 'gobelet'    => 1.5,
            'tupperware' => 2.5, 'jouet'      => 1.0, 'emballage'  => 1.2,
            'bidon'      => 1.5, 'bac'        => 1.0, 'barquette'  => 1.5,
            'film'       => 1.0, 'sachet'     => 1.5, 'jerrycan'   => 1.5,
            'tuyau'      => 1.2, 'gaine'      => 1.0,
        ],
        'Papier' => [
            'papier'     => 3.0, 'journal'    => 2.5, 'gazette'    => 2.5,
            'magazine'   => 2.5, 'livre'      => 2.0, 'cahier'     => 2.5,
            'feuille'    => 1.5, 'page'       => 1.0, 'enveloppe'  => 2.0,
            'imprimé'    => 1.5, 'brochure'   => 2.0, 'prospectus' => 2.5,
            'tract'      => 2.0, 'affiche'    => 1.5, 'dépliant'   => 2.0,
            'papeterie'  => 2.5, 'ramette'    => 2.5, 'photocopie' => 2.0,
            'ticket'     => 1.5, 'reçu'       => 1.5, 'facture'    => 1.0,
        ],
        'Carton' => [
            'carton'     => 3.0, 'cartonnage' => 3.0, 'cartonné'   => 2.5,
            'boîte'      => 2.0, 'boite'      => 2.0, 'colis'      => 2.0,
            'emballage'  => 1.5, 'packaging'  => 1.5, 'coffret'    => 1.5,
            'étui'       => 1.5, 'pack'       => 1.0, 'ondulé'     => 2.5,
            'krafts'     => 2.5, 'kraft'      => 2.5, 'carton-pâte' => 3.0,
        ],
        'Verre' => [
            'verre'      => 3.0, 'vitre'      => 2.5, 'vitrage'    => 2.5,
            'cristal'    => 2.5, 'pyrex'      => 2.5, 'miroir'     => 2.0,
            'bocal'      => 2.5, 'flacon'     => 2.0, 'pot'        => 1.0,
            'bouteille'  => 1.5, 'carafe'     => 2.5, 'vase'       => 2.0,
            'ampoule'    => 1.5, 'verrerie'   => 3.0, 'fiole'      => 2.0,
            'vitrail'    => 2.5, 'pare-brise'  => 2.0, 'fenêtre'   => 1.0,
        ],
        'Métal' => [
            'métal'      => 3.0, 'metal'      => 3.0, 'métallique' => 3.0,
            'fer'        => 2.5, 'acier'      => 3.0, 'inox'       => 3.0,
            'aluminium'  => 3.0, 'alu'        => 2.5, 'cuivre'     => 3.0,
            'laiton'     => 3.0, 'bronze'     => 3.0, 'zinc'       => 3.0,
            'étain'      => 3.0, 'plomb'      => 2.5, 'chrome'     => 2.5,
            'titane'     => 3.0, 'fonte'      => 3.0, 'tôle'       => 2.5,
            'ferraille'  => 3.0, 'canette'    => 2.5, 'conserve'   => 2.0,
            'casserole'  => 2.0, 'poêle'      => 1.5, 'clou'       => 2.0,
            'vis'        => 1.5, 'boulon'     => 2.0, 'grillage'   => 2.0,
            'câble'      => 1.5, 'fil'        => 1.0, 'chaîne'     => 1.5,
            'radiateur'  => 1.5, 'gouttière'  => 2.0, 'robinet'    => 1.5,
        ],
        'Tissu' => [
            'tissu'      => 3.0, 'textile'    => 3.0, 'toile'      => 2.5,
            'coton'      => 3.0, 'laine'      => 3.0, 'soie'       => 3.0,
            'lin'        => 2.5, 'polyester'  => 2.5, 'jean'       => 2.5,
            'denim'      => 2.5, 'velours'    => 2.5, 'satin'      => 2.5,
            'cuir'       => 2.0, 'daim'       => 2.0, 'feutre'     => 2.0,
            'moquette'   => 2.5, 'tapisserie' => 2.0, 'rideau'     => 2.0,
            // Vêtements
            'vêtement'   => 2.5, 'habit'      => 2.0, 'robe'       => 2.0,
            'pantalon'   => 2.0, 'chemise'    => 2.0, 'pull'       => 2.0,
            'manteau'    => 2.0, 'veste'      => 2.0, 'chaussette' => 2.0,
            'écharpe'    => 2.0, 'bonnet'     => 1.5, 'gant'       => 1.5,
            'couverture' => 2.0, 'drap'       => 2.0, 'nappe'      => 2.0,
            'serviette'  => 1.5, 'torchon'    => 2.0, 'coussin'    => 1.5,
            'oreiller'   => 1.5, 'matelas'    => 1.5, 'tapis'      => 2.0,
            'sac'        => 1.0, 'chiffon'    => 2.5,
        ],
    ];

    // ──────────────────────────────────────────────
    //  DICTIONNAIRE : type matériau
    // ──────────────────────────────────────────────
    private const TYPE_KEYWORDS = [
        'Naturel' => [
            'naturel'    => 3.0, 'naturelle'  => 3.0, 'nature'     => 2.0,
            'bio'        => 2.5, 'biologique' => 2.5, 'organique'  => 2.5,
            'brut'       => 2.0, 'bois'       => 1.5, 'coton'      => 1.5,
            'laine'      => 1.5, 'lin'        => 1.5, 'soie'       => 1.5,
            'bambou'     => 2.0, 'chanvre'    => 2.0, 'jute'       => 2.0,
            'liège'      => 2.0, 'cuir'       => 1.5, 'pierre'     => 1.5,
            'argile'     => 1.5, 'terre'      => 1.0, 'végétal'    => 2.0,
        ],
        'Durable' => [
            'durable'    => 3.0, 'solide'     => 2.0, 'robuste'    => 2.0,
            'résistant'  => 2.0, 'résistante' => 2.0, 'longue durée' => 2.5,
            'qualité'    => 1.5, 'inusable'   => 2.5, 'costaud'    => 2.0,
            'métal'      => 1.5, 'acier'      => 1.5, 'inox'       => 1.5,
            'fonte'      => 1.5, 'béton'      => 1.5, 'permanent'  => 2.0,
            'fer'        => 1.0, 'verre'      => 1.0,
        ],
        'Écologique' => [
            'écologique' => 3.0, 'écolo'      => 3.0, 'eco'        => 2.5,
            'éco'        => 2.5, 'vert'       => 2.0, 'verte'      => 2.0,
            'green'      => 2.0, 'environnement' => 2.5, 'planète'  => 2.0,
            'recyclé'    => 2.5, 'recyclée'   => 2.5, 'compostable' => 2.5,
            'biodégradable' => 3.0, 'renouvelable' => 2.5,
            'propre'     => 1.5, 'durable'    => 1.5, 'responsable' => 2.0,
        ],
        'Zéro déchet' => [
            'zéro déchet' => 3.0, 'zero déchet' => 3.0, 'zero dechet' => 3.0,
            'zéro-déchet' => 3.0, 'sans déchet' => 2.5, 'sans emballage' => 2.5,
            'vrac'       => 2.5, 'minimaliste' => 2.0, 'sans plastique' => 2.5,
            'réduction'  => 1.5, 'compost'    => 2.0, 'compostage' => 2.0,
            'minimal'    => 1.5, 'économe'    => 1.5,
        ],
        'Réutilisable' => [
            'réutilisable' => 3.0, 'réutiliser' => 2.5, 'réemployer' => 2.5,
            'réemploi'   => 2.5, 'seconde main' => 2.5, 'occasion'  => 2.0,
            'vintage'    => 2.0, 'récup'      => 2.5, 'récupération' => 2.5,
            'recyclable' => 2.5, 'recyclage'  => 2.5, 'upcycling'  => 2.5,
            'surcyclage' => 2.5, 'revaloriser' => 2.0, 'transformer' => 1.5,
            'bricolage'  => 1.5, 'réparer'    => 2.0, 'restaurer'  => 2.0,
            'rénover'    => 2.0, 'retaper'    => 2.0, 'customiser' => 1.5,
        ],
    ];

    // ──────────────────────────────────────────────
    //  DICTIONNAIRE : état du produit
    // ──────────────────────────────────────────────
    private const STATE_KEYWORDS = [
        'Bon' => [
            'bon'       => 2.5, 'bonne'      => 2.5, 'neuf'       => 3.0,
            'neuve'     => 3.0, 'excellent'  => 3.0, 'excellente' => 3.0,
            'parfait'   => 3.0, 'parfaite'   => 3.0, 'impeccable' => 3.0,
            'intact'    => 2.5, 'intacte'    => 2.5, 'nickel'     => 2.5,
            'propre'    => 1.5, 'fonctionnel' => 2.0, 'fonctionnelle' => 2.0,
            'marche'    => 1.5, 'bien'       => 1.5,
        ],
        'Moyen' => [
            'moyen'      => 2.5, 'moyenne'    => 2.5, 'correct'    => 2.5,
            'correcte'   => 2.5, 'passable'   => 2.5, 'acceptable' => 2.0,
            'usé'        => 2.0, 'usée'       => 2.0, 'usure'      => 2.0,
            'rayé'       => 2.0, 'rayée'      => 2.0, 'rayure'     => 2.0,
            'taché'      => 2.0, 'tachée'     => 2.0, 'tache'      => 1.5,
            'défraîchi'  => 2.0, 'vieux'      => 2.0, 'vieille'    => 2.0,
            'ancien'     => 1.5, 'ancienne'   => 1.5, 'fatigué'    => 2.0,
            'occasion'   => 1.5, 'normal'     => 1.5, 'standard'   => 1.5,
        ],
        'Mauvais' => [
            'mauvais'    => 3.0, 'mauvaise'   => 3.0, 'cassé'      => 3.0,
            'cassée'     => 3.0, 'brisé'      => 3.0, 'brisée'     => 3.0,
            'abîmé'      => 2.5, 'abîmée'     => 2.5, 'détérioré'  => 2.5,
            'détériorée' => 2.5, 'fichu'      => 2.5, 'foutu'      => 2.5,
            'irréparable' => 3.0, 'hors service' => 3.0, 'hs'      => 2.5,
            'panne'      => 2.0, 'défectueux' => 2.5, 'défectueuse' => 2.5,
            'pourri'     => 2.5, 'pourrie'    => 2.5, 'rouillé'    => 2.5,
            'rouillée'   => 2.5, 'troué'      => 2.5, 'trouée'     => 2.5,
            'déchiré'    => 2.5, 'déchirée'   => 2.5, 'fêlé'       => 2.5,
            'fêlée'      => 2.5, 'fissuré'    => 2.5, 'fissurée'   => 2.5,
            'mort'       => 2.0, 'morte'      => 2.0, 'crevé'      => 2.0,
        ],
    ];

    // ─────────────────────────────────
    //  Mapping objets → catégorie par défaut
    //  (quand aucun matériau n'est explicitement mentionné)
    // ─────────────────────────────────
    private const OBJECT_TYPE_MAP = [
        // Meubles → souvent en bois
        'chaise' => 'meuble', 'table' => 'meuble', 'armoire' => 'meuble',
        'étagère' => 'meuble', 'commode' => 'meuble', 'bureau' => 'meuble',
        'lit' => 'meuble', 'tabouret' => 'meuble', 'banc' => 'meuble',
        'bibliothèque' => 'meuble', 'placard' => 'meuble', 'buffet' => 'meuble',
        'canapé' => 'meuble', 'fauteuil' => 'meuble', 'divan' => 'meuble',
        // Contenants
        'bouteille' => 'contenant', 'bocal' => 'contenant', 'pot' => 'contenant',
        'boîte' => 'contenant', 'boite' => 'contenant', 'carafe' => 'contenant',
        'vase' => 'contenant', 'flacon' => 'contenant', 'bidon' => 'contenant',
        'gobelet' => 'contenant', 'canette' => 'contenant',
        // Vêtements
        'vêtement' => 'textile', 'robe' => 'textile', 'pantalon' => 'textile',
        'chemise' => 'textile', 'pull' => 'textile', 'manteau' => 'textile',
        'veste' => 'textile', 'chaussette' => 'textile', 'jean' => 'textile',
        // Documents
        'journal' => 'document', 'magazine' => 'document', 'livre' => 'document',
        'cahier' => 'document', 'enveloppe' => 'document',
    ];

    private const TUNISIAN_CITIES = [
        'Tunis', 'Ariana', 'Ben Arous', 'Manouba', 'Nabeul', 'Zaghouan', 'Bizerte', 'Béja', 'Jendouba', 'Le Kef',
        'Siliana', 'Sousse', 'Monastir', 'Mahdia', 'Sfax', 'Kairouan', 'Kasserine', 'Sidi Bouzid', 'Gabès',
        'Médenine', 'Tataouine', 'Gafsa', 'Tozeur', 'Kebili'
    ];

    /**
     * Analyse un texte et retourne les détections avec scores de confiance.
     *
     * @return array{
     *   category: array{value: string|null, confidence: float, scores: array<string, float>},
     *   type: array{value: string|null, confidence: float, scores: array<string, float>},
     *   state: array{value: string|null, confidence: float, scores: array<string, float>},
     *   detected_objects: string[],
     *   quantity: int|null,
     *   origin: string|null,
     *   impact: float|null,
     *   input_text: string
     * }
     */
    public function analyze(string $text): array
    {
        $normalizedText = $this->normalizeText($text);
        $words = $this->tokenize($normalizedText);
        $bigrams = $this->buildBigrams($words);

        // Score des catégories
        $categoryScores = $this->computeScores($words, $bigrams, self::CATEGORY_KEYWORDS);
        $typeScores     = $this->computeScores($words, $bigrams, self::TYPE_KEYWORDS);
        $stateScores    = $this->computeScores($words, $bigrams, self::STATE_KEYWORDS);

        // Détecter les objets mentionnés
        $detectedObjects = $this->detectObjects($words);

        // Déterminer les meilleurs résultats
        $bestCategory = $this->getBest($categoryScores);
        $bestType     = $this->getBest($typeScores);
        $bestState    = $this->getBest($stateScores);

        return [
            'category' => [
                'value'      => $bestCategory['value'],
                'confidence' => $bestCategory['confidence'],
                'scores'     => $categoryScores,
            ],
            'type' => [
                'value'      => $bestType['value'],
                'confidence' => $bestType['confidence'],
                'scores'     => $typeScores,
            ],
            'state' => [
                'value'      => $bestState['value'],
                'confidence' => $bestState['confidence'],
                'scores'     => $stateScores,
            ],
            'detected_objects' => $detectedObjects,
            'quantity'         => $this->detectQuantity($text),
            'origin'           => $this->detectOrigin($text),
            'impact'           => $this->detectImpact($text),
            'input_text'       => $text,
        ];
    }

    /**
     * Détecte la quantité (chiffres ou mots)
     */
    private function detectQuantity(string $text): ?int
    {
        // Recherche de chiffres
        if (preg_match('/\b(\d+)\b/', $text, $matches)) {
            return (int) $matches[1];
        }

        $map = [
            'un' => 1, 'une' => 1, 'deux' => 2, 'trois' => 3, 'quatre' => 4, 'cinq' => 5,
            'six' => 6, 'sept' => 7, 'huit' => 8, 'neuf' => 9, 'dix' => 10
        ];

        foreach ($map as $word => $val) {
            if (preg_match('/\b' . $word . '\b/iu', $text)) {
                return $val;
            }
        }

        return null;
    }

    /**
     * Détecte l'origine (villes Tunisiennes)
     */
    private function detectOrigin(string $text): ?string
    {
        foreach (self::TUNISIAN_CITIES as $city) {
            if (preg_match('/\b' . preg_quote($city, '/') . '\b/iu', $text)) {
                return $city;
            }
        }
        return null;
    }

    /**
     * Détecte l'impact écologique (chiffre après "impact")
     */
    private function detectImpact(string $text): ?float
    {
        if (preg_match('/impact\s*(?:écologique\s*)?(?:de\s+)?(\d+(?:[\.,]\d+)?)/iu', $text, $matches)) {
            return (float) str_replace(',', '.', $matches[1]);
        }
        return null;
    }

    /**
     * Normalise le texte : minuscules, supprime accents pour comparaison,
     * mais garde aussi la version accentuée.
     */
    private function normalizeText(string $text): string
    {
        return mb_strtolower(trim($text), 'UTF-8');
    }

    /**
     * Tokenise le texte en mots individuels.
     * @return string[]
     */
    private function tokenize(string $text): array
    {
        // Supprime la ponctuation sauf les tirets et apostrophes
        $cleaned = preg_replace('/[^\p{L}\p{N}\s\'-]/u', ' ', $text);
        $words = preg_split('/\s+/', $cleaned, -1, PREG_SPLIT_NO_EMPTY);
        return array_filter($words, fn(string $w) => mb_strlen($w) > 1);
    }

    /**
     * Construit les bigrammes pour détecter les expressions composées.
     * @param string[] $words
     * @return string[]
     */
    private function buildBigrams(array $words): array
    {
        $bigrams = [];
        $wordsIndexed = array_values($words);
        for ($i = 0; $i < count($wordsIndexed) - 1; $i++) {
            $bigrams[] = $wordsIndexed[$i] . ' ' . $wordsIndexed[$i + 1];
        }
        return $bigrams;
    }

    /**
     * Calcule les scores pour chaque label à partir des mots-clés.
     *
     * @param string[]              $words
     * @param string[]              $bigrams
     * @param array<string, array<string, float>> $dictionary
     * @return array<string, float>
     */
    private function computeScores(array $words, array $bigrams, array $dictionary): array
    {
        $scores = [];
        foreach ($dictionary as $label => $keywords) {
            $score = 0.0;
            foreach ($keywords as $keyword => $weight) {
                // Vérifier les bigrammes d'abord (expressions composées)
                if (str_contains($keyword, ' ')) {
                    foreach ($bigrams as $bigram) {
                        if ($bigram === $keyword) {
                            $score += $weight * 1.5; // Bonus pour expression complète
                        }
                    }
                } else {
                    // Mot simple
                    foreach ($words as $word) {
                        if ($word === $keyword) {
                            $score += $weight;
                        } elseif (mb_strlen($keyword) >= 4 && str_starts_with($word, mb_substr($keyword, 0, -1))) {
                            // Correspondance partielle (gestion pluriel/conjugaison)
                            $score += $weight * 0.7;
                        }
                    }
                }
            }
            $scores[$label] = round($score, 2);
        }
        return $scores;
    }

    /**
     * Détecte les objets mentionnés dans le texte.
     * @param string[] $words
     * @return string[]
     */
    private function detectObjects(array $words): array
    {
        $objects = [];
        foreach ($words as $word) {
            if (isset(self::OBJECT_TYPE_MAP[$word])) {
                $objectType = self::OBJECT_TYPE_MAP[$word];
                $objects[$word] = $objectType;
            }
        }
        return $objects;
    }

    /**
     * Retourne le meilleur résultat avec sa confiance.
     * @param array<string, float> $scores
     * @return array{value: string|null, confidence: float}
     */
    private function getBest(array $scores): array
    {
        if (empty($scores) || max($scores) === 0.0) {
            return ['value' => null, 'confidence' => 0.0];
        }

        $maxScore = max($scores);
        $bestLabel = array_search($maxScore, $scores, true);
        $totalScore = array_sum($scores);

        // Calcul de la confiance (0-100%)
        $confidence = $totalScore > 0 ? round(($maxScore / $totalScore) * 100, 1) : 0;

        // Seuil minimum de confiance : au moins 1.5 de score brut
        if ($maxScore < 1.5) {
            return ['value' => null, 'confidence' => 0.0];
        }

        return [
            'value'      => (string) $bestLabel,
            'confidence' => $confidence,
        ];
    }
}
