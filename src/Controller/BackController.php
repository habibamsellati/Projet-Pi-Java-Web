<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class BackController extends AbstractController
{
    #[Route('/back', name: 'back')]
    public function index(\App\Repository\EvenementRepository $evenementRepository, \App\Repository\ReservationRepository $reservationRepository): Response
    {
        // Stats reelles
        $evenementStats = $evenementRepository->getStats();
        $reservationStats = $reservationRepository->getStats();
        $capacityData = $evenementRepository->getCapacityData();

        // Tableau d'utilisateurs fictifs (on peut le garder ou le remplacer plus tard)
        $users = [
            ['id' => 1, 'name' => 'Marie Claire', 'email' => 'marie@mail.com', 'active' => true],
            ['id' => 2, 'name' => 'Ahmed', 'email' => 'ahmed@mail.com', 'active' => true],
            ['id' => 3, 'name' => 'Sonia', 'email' => 'sonia@mail.com', 'active' => false],
        ];

        return $this->render('admin/index.html.twig', [
            'users' => $users,
            'evenementStats' => $evenementStats,
            'reservationStats' => $reservationStats,
            'capacityData' => $capacityData,
        ]);
    }
}
