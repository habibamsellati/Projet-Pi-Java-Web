<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait BackModuleAccessTrait
{
    private function isAdminUser(): bool
    {
        /** @var User|null $user */
        $user = $this->getUser();
        return $user !== null && $user->getRole() === 'ADMIN';
    }

    private function checkModuleAccess(Request $request, string $module): ?Response
    {
        if ($this->isAdminUser()) {
            return null;
        }
        $access = (array) $request->getSession()->get('responsable_access', []);
        if (!empty($access[$module])) {
            return null;
        }
        $this->addFlash('error', 'Acces refuse. Veuillez vous authentifier.');
        return $this->redirectToRoute('back');
    }
}
