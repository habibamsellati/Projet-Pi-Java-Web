<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\Evenement;
use App\Service\SmartFillService;
use App\Repository\EvenementRepository;
use App\Repository\ReservationRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/artisan')]
class EventApiController extends AbstractController
{
    /**
     * Resolves the artisan user via Session or X-API-KEY header
     */
    private function resolveArtisan(Request $request, UserRepository $userRepo): ?User
    {
        // 1. Check Session First (Standard)
        $user = $this->getUser();
        if ($user instanceof User) {
            return $user;
        }

        // 2. Check X-API-KEY Header (Excellent Upgrade)
        $apiKey = $request->headers->get('X-API-KEY');
        if ($apiKey) {
            return $userRepo->findOneBy(['apiKey' => $apiKey]);
        }

        return null;
    }

    private function getArtisanIdentifier(User $user): string
    {
        $id = trim(sprintf('%s %s', (string) $user->getPrenom(), (string) $user->getNom()));
        return $id !== '' ? $id : (string)$user->getUserIdentifier();
    }
    #[Route('/stats', name: 'api_artisan_stats', methods: ['GET'])]
    public function getStats(Request $request, UserRepository $userRepo, EvenementRepository $eventRepo, ReservationRepository $resRepo): JsonResponse
    {
        $user = $this->resolveArtisan($request, $userRepo);
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized or Invalid API Key'], 401);
        }

        $artisanIdentifier = $this->getArtisanIdentifier($user);
        $events = $eventRepo->findBy(['artisan' => $artisanIdentifier]);
        
        $totalEvents = count($events);
        $totalRevenue = 0;
        $totalReservations = 0;
        $categoryStats = [];

        foreach ($events as $event) {
            $confirmedReservations = $resRepo->findBy(['evenement' => $event, 'statut' => 'confirme']);
            $count = count($confirmedReservations);
            
            $totalReservations += $count;
            foreach ($confirmedReservations as $res) {
                $totalRevenue += ($res->getNbPlaces() * $event->getPrix());
            }

            $type = $event->getTypeArt() ?: 'Autre';
            if (!isset($categoryStats[$type])) {
                $categoryStats[$type] = 0;
            }
            $categoryStats[$type]++;
        }

        return new JsonResponse([
            'artisan' => $artisanIdentifier,
            'summary' => [
                'total_events' => $totalEvents,
                'total_confirmed_reservations' => $totalReservations,
                'total_revenue_tnd' => $totalRevenue,
            ],
            'categories' => $categoryStats,
            'timestamp' => time()
        ]);
    }

    #[Route('/events', name: 'api_artisan_events', methods: ['GET'])]
    public function listEvents(Request $request, UserRepository $userRepo, EvenementRepository $eventRepo, ReservationRepository $resRepo): JsonResponse
    {
        $user = $this->resolveArtisan($request, $userRepo);
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized or Invalid API Key'], 401);
        }

        $artisanIdentifier = $this->getArtisanIdentifier($user);
        $events = $eventRepo->findBy(['artisan' => $artisanIdentifier]);

        $data = [];
        foreach ($events as $event) {
            $confirmed = count($resRepo->findBy(['evenement' => $event, 'statut' => 'confirme']));
            $data[] = [
                'id' => $event->getId(),
                'nom' => $event->getNom(),
                'date' => $event->getDateDebut() ? $event->getDateDebut()->format('Y-m-d') : null,
                'capacite' => $event->getCapacite(),
                'reservations_confirmees' => $confirmed,
                'taux_remplissage' => $event->getCapacite() > 0 ? round(($confirmed / $event->getCapacite()) * 100, 1) : 0,
                'prix' => $event->getPrix()
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/verify/{id}', name: 'api_ticket_verify', methods: ['GET'])]
    public function verifyTicket(ReservationRepository $resRepo, int $id): JsonResponse
    {
        $reservation = $resRepo->find($id);
        if (!$reservation) {
            return new JsonResponse(['valid' => false, 'error' => 'Not Found'], 404);
        }

        return new JsonResponse([
            'valid' => $reservation->getStatut() === 'confirme',
            'id' => $reservation->getId(),
            'client' => $reservation->getUser() ? $reservation->getUser()->getPrenom() . ' ' . $reservation->getUser()->getNom() : 'Anonyme',
            'evenement' => $reservation->getEvenement()->getNom(),
            'nb_places' => $reservation->getNbPlaces(),
        ]);
    }
    #[Route('/events/{id}/ai-stats', name: 'api_artisan_event_ai_stats', methods: ['GET'])]
    public function getEventAiStats(Request $request, UserRepository $userRepo, Evenement $event, SmartFillService $smartFillService): JsonResponse
    {
        $user = $this->resolveArtisan($request, $userRepo);
        if (!$user || $event->getArtisan() !== $this->getArtisanIdentifier($user)) {
            return new JsonResponse(['error' => 'Unauthorized or access denied for this event'], 403);
        }

        $prediction = $smartFillService->predictFillRate($event);
        $analysis = $smartFillService->calculateStrategicAnalysis($event, $prediction);

        return new JsonResponse([
            'id' => $event->getId(),
            'prediction' => $prediction,
            'success_score' => $smartFillService->calculateSuccessScore($event, $prediction),
            'elasticity' => $smartFillService->calculatePriceElasticity($prediction, (float)$event->getPrix()),
            'strategic_checklist' => $smartFillService->getStrategicChecklist($event, $analysis),
            'marketing_tags' => $smartFillService->generateStrategicBrief($event, $prediction)
        ]);
    }
}
