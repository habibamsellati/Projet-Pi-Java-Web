<?php

namespace App\Service;

use App\Entity\Article;

/**
 * Service de gestion des règles métier pour l'entité Article
 */
class ArticleManager
{
    /**
     * Règle 1: Le titre est obligatoire et doit contenir au moins 5 caractères
     * Règle 2: Le contenu doit contenir au moins 20 caractères
     * Règle 3: Le nombre de likes ne peut pas être négatif
     * 
     * @param Article $article
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validate(Article $article): bool
    {
        // Règle 1: Le titre est obligatoire et doit contenir au moins 5 caractères
        if (empty($article->getTitre()) || strlen($article->getTitre()) < 5) {
            throw new \InvalidArgumentException('Le titre est obligatoire et doit contenir au moins 5 caractères');
        }

        // Règle 2: Le contenu doit contenir au moins 20 caractères
        if (empty($article->getContenu()) || strlen($article->getContenu()) < 20) {
            throw new \InvalidArgumentException('Le contenu doit contenir au moins 20 caractères');
        }

        // Règle 3: Le nombre de likes ne peut pas être négatif
        if ($article->getLikes() !== null && $article->getLikes() < 0) {
            throw new \InvalidArgumentException('Le nombre de likes ne peut pas être négatif');
        }

        return true;
    }

    /**
     * Incrémente le nombre de likes d'un article
     * 
     * @param Article $article
     * @return int Le nouveau nombre de likes
     */
    public function incrementLikes(Article $article): int
    {
        $currentLikes = $article->getLikes() ?? 0;
        $newLikes = $currentLikes + 1;
        $article->setLikes($newLikes);
        
        return $newLikes;
    }

    /**
     * Vérifie si un article est populaire (plus de 10 likes)
     * 
     * @param Article $article
     * @return bool
     */
    public function isPopular(Article $article): bool
    {
        return ($article->getLikes() ?? 0) > 10;
    }

    /**
     * Calcule le nombre de mots dans le contenu
     * 
     * @param Article $article
     * @return int
     */
    public function getWordCount(Article $article): int
    {
        $contenu = $article->getContenu() ?? '';
        return str_word_count(strip_tags($contenu));
    }
}
