<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        // On récupère uniquement les réservations de l'utilisateur connecté
        $user = $this->getUser();
        
        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservationRepository->findBy(['user' => $user]),
        ]);
    }

    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, \App\Repository\EvenementRepository $evenementRepository): Response
    {
        $reservation = new Reservation();
        $reservation->setDateReservation(new \DateTime());
        $reservation->setStatut('en_attente');
        // On lie automatiquement l'utilisateur connecté
        $reservation->setUser($this->getUser());

        // Pré-sélection de l'événement si l'ID est passé en paramètre
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
        // Sécurité : on vérifie que la réservation appartient bien à l'utilisateur
        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réservation.');
        }

        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        // Sécurité
        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réservation.');
        }

        // Proactive check for expiry on GET
        if ($reservation->getStatut() === 'en_attente' && $reservation->getCreatedAt()) {
            $expiry = $reservation->getCreatedAt()->modify('+3 hours');
            if ($expiry < new \DateTimeImmutable()) {
                $this->addFlash('warning', 'Le délai de confirmation de 3 heures est dépassé. Vous ne pouvez plus confirmer cette réservation.');
            }
        }

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Enforce 3-hour limit for confirmation
            if ($reservation->getStatut() === 'confirme' && $reservation->getCreatedAt()) {
                $expiry = $reservation->getCreatedAt()->modify('+3 hours');
                if ($expiry < new \DateTimeImmutable()) {
                    $this->addFlash('error', 'Désolé, le délai de confirmation de 3 heures est dépassé. Cette réservation ne peut plus être confirmée.');
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
        // Sécurité
        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réservation.');
        }

        if ($this->isCsrfTokenValid('cancel'.$reservation->getId(), $request->request->get('_token'))) {
            $reservation->setStatut('annule');
            $entityManager->flush();
            $this->addFlash('success', 'Réservation annulée.');
        }

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        // Sécurité
        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réservation.');
        }

        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation supprimée.');
        }

        return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
}