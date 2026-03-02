<?php

namespace App\Service;

use App\Entity\User;

class UserManager
{
    public function validate(User $user): bool
    {
        if (empty($user->getNom()) || strlen($user->getNom()) < 2) {
            throw new \InvalidArgumentException('Le nom doit contenir au moins 2 caractères');
        }

        if (empty($user->getPrenom()) || strlen($user->getPrenom()) < 2) {
            throw new \InvalidArgumentException('Le prénom doit contenir au moins 2 caractères');
        }

        if (!filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('L\'email doit être valide');
        }

        if ($user->getTelephone() && !preg_match('/^(\+216|00216)?[2-9]\d{7}$/', $user->getTelephone())) {
            throw new \InvalidArgumentException('Le numéro de téléphone doit être au format tunisien valide');
        }

        $rolesValides = ['CLIENT', 'ADMIN', 'ARTISANT', 'LIVREUR'];
        if ($user->getRole() && !in_array(strtoupper($user->getRole()), $rolesValides, true)) {
            throw new \InvalidArgumentException('Le rôle doit être: CLIENT, ADMIN, ARTISANT ou LIVREUR');
        }

        return true;
    }

    public function isActive(User $user): bool
    {
        return $user->getStatut() === 'actif' && $user->getDeletedAt() === null;
    }

    public function hasRole(User $user, string $role): bool
    {
        return strtoupper($user->getRole() ?? '') === strtoupper($role);
    }
}
