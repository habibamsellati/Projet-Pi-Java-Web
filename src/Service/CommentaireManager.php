<?php

namespace App\Service;

use App\Entity\Commentaire;

class CommentaireManager
{
    public function validate(Commentaire $commentaire): bool
    {
        if (empty($commentaire->getContenu()) || strlen($commentaire->getContenu()) < 10) {
            throw new \InvalidArgumentException('Le contenu doit contenir au moins 10 caractères');
        }

        return true;
    }

    public function getWordCount(Commentaire $commentaire): int
    {
        $contenu = $commentaire->getContenu() ?? '';
        return str_word_count(strip_tags($contenu));
    }
}
