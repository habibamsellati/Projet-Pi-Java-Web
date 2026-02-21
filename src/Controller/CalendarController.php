<?php

namespace App\Controller;

use App\Repository\PropositionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

class CalendarController extends AbstractController
{
    private $repo;
    private $security;

    public function __construct(PropositionRepository $repo, Security $security)
    {
        $this->repo = $repo;
        $this->security = $security;
    }

    #[Route('/produit-recyclable/calendar/events', name: 'fc_load_events_recyclable')]
    public function loadEvents(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse([]);
        }

        // On récupère les propositions soumises par l'artisan
        $propositions = $this->repo->findBy(['user' => $user]);
        $events = [];

        foreach ($propositions as $proposition) {
            if (!$proposition->getDate()) continue;
            
            $events[] = [
                'id' => $proposition->getId(),
                'title' => $proposition->getTitre(),
                'start' => $proposition->getDate()->format('Y-m-d\TH:i:s'),
                'backgroundColor' => '#8b5e4a',
                'borderColor' => 'transparent',
                'extendedProps' => [
                    'description' => $proposition->getDescription(),
                    'statut' => $proposition->getStatut()
                ]
            ];
        }

        return new JsonResponse($events);
    }
}
