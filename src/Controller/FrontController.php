<?php
// src/Controller/FrontController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontController extends AbstractController
{
    private function checkFrontAccess(string $requiredRole): ?Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Hors privilege');
            return $this->redirectToRoute('home');
        }

        $role = strtoupper((string) $user->getRole());
        if (str_starts_with($role, 'ROLE_')) {
            $role = substr($role, 5);
        }
        $required = strtoupper($requiredRole);
        if (str_starts_with($required, 'ROLE_')) {
            $required = substr($required, 5);
        }

        if ($role === 'ADMIN' || $role === $required) {
            return null;
        }

        $this->addFlash('error', 'Hors privilege');
        return $this->redirectToRoute('home');
    }
    #[Route('/', name: 'home')]
    public function home(): Response
    {
        // Affiche directement le template home/index.html.twig
        return $this->render('home/index.html.twig');
    }

    #[Route('/admin', name: 'front_admin')]
    public function adminFront(): Response
    {
        $check = $this->checkFrontAccess('ADMIN');
        if ($check) return $check;

        return $this->render('front/admin.html.twig');
    }

    #[Route('/client', name: 'front_client')]
    public function clientFront(): Response
    {
        $check = $this->checkFrontAccess('CLIENT');
        if ($check) return $check;

        return $this->render('front/client.html.twig');
    }

    #[Route('/artisan', name: 'front_artisan')]
    public function artisanFront(): Response
    {
        $check = $this->checkFrontAccess('ARTISANT');
        if ($check) return $check;

        return $this->render('front/artisan.html.twig');
    }

    #[Route('/livreur', name: 'front_livreur')]
    public function livreurFront(): Response
    {
        $check = $this->checkFrontAccess('LIVREUR');
        if ($check) return $check;

        $user = $this->getUser();
        if ($user) {
            return $this->redirectToRoute('app_livreur_dashboard', ['id' => $user->getId()]);
        }
        return $this->render('front/livreur.html.twig');
    }
}
