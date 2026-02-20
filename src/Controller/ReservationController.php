<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\User;
use App\Form\ReservationType;
use App\Repository\EvenementRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reservation')]
final class ReservationController extends AbstractController
{
    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        if (!$user instanceof User) {
            return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
        }
        // Only CLIENT sees their own reservations; ARTISANT and others see an empty list (view-only page)
        $reservations = [];
        if ($user->getRole() === User::ROLE_CLIENT) {
            $reservations = $reservationRepository->findBy(['user' => $user], ['id' => 'DESC']);
        }
        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EvenementRepository $evenementRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        if (!$user instanceof User || $user->getRole() !== User::ROLE_CLIENT) {
            $this->addFlash('error', 'Seuls les clients peuvent créer une réservation.');
            return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
        }
        $reservation = new Reservation();
        $reservation->setDateReservation(new \DateTime());
        $reservation->setStatut('en_attente');
        $reservation->setUser($user);

        $eventId = $request->query->get('event');
        if ($eventId) {
            $evenement = $evenementRepository->find($eventId);
            if ($evenement) {
                $reservation->setEvenement($evenement);
            }
        }

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Votre réservation a été enregistrée avec succès.');
            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        if (!$user instanceof User || $user->getRole() !== User::ROLE_CLIENT) {
            $this->addFlash('error', 'Seuls les clients peuvent accéder à leurs réservations.');
            return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
        }
        if ($reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réservation.');
        }
        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        if (!$user instanceof User || $user->getRole() !== User::ROLE_CLIENT) {
            $this->addFlash('error', 'Seuls les clients peuvent modifier leurs réservations.');
            return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
        }
        if ($reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réservation.');
        }

        if ($reservation->getStatut() === 'en_attente' && $reservation->getCreatedAt()) {
            $expiry = $reservation->getCreatedAt()->modify('+3 hours');
            if ($expiry < new \DateTimeImmutable()) {
                $this->addFlash('warning', 'Le délai de confirmation de 3 heures est dépassé.');
            }
        }

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($reservation->getStatut() === 'confirme' && $reservation->getCreatedAt()) {
                $expiry = $reservation->getCreatedAt()->modify('+3 hours');
                if ($expiry < new \DateTimeImmutable()) {
                    $this->addFlash('error', 'Le délai de confirmation de 3 heures est dépassé.');
                    $reservation->setStatut('annule');
                    $entityManager->flush();
                    return $this->redirectToRoute('app_reservation_index');
                }
            }
            $entityManager->flush();
            $this->addFlash('success', 'La réservation a été mise à jour.');
            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/annuler', name: 'app_reservation_cancel', methods: ['POST'])]
    public function cancel(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        if (!$user instanceof User || $user->getRole() !== User::ROLE_CLIENT) {
            $this->addFlash('error', 'Seuls les clients peuvent annuler leurs réservations.');
            return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
        }
        if ($reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réservation.');
        }
        if ($this->isCsrfTokenValid('cancel' . $reservation->getId(), (string) $request->request->get('_token'))) {
            $reservation->setStatut('annule');
            $entityManager->flush();
            $this->addFlash('success', 'Réservation annulée.');
        }
        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        if (!$user instanceof User || $user->getRole() !== User::ROLE_CLIENT) {
            $this->addFlash('error', 'Seuls les clients peuvent supprimer leurs réservations.');
            return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
        }
        if ($reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réservation.');
        }
        if ($this->isCsrfTokenValid('delete' . $reservation->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation supprimée.');
        }
        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
}
