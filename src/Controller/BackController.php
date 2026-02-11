<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class BackController extends AbstractController
{
    #[Route('/back', name: 'back')]
    public function index(): Response
    {
        // Tableau d'utilisateurs fictifs
        $users = [
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@mail.com', 'active' => true],
            ['id' => 2, 'name' => 'Bob', 'email' => 'bob@mail.com', 'active' => false],
            ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@mail.com', 'active' => true],
        ];

        return $this->render('admin/index.html.twig', [
            'users' => $users, // on passe la variable à Twig
        ]);
    }
}
