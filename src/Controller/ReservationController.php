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
use App\Service\QRCodeService;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/reservation')]
final class ReservationController extends AbstractController
{
    #[Route('/verify/{id}', name: 'app_reservation_verify', methods: ['GET'])]
    public function verify(Reservation $reservation): Response
    {
        $confirme = $reservation->getStatut() === 'confirme';
        $couleur  = $confirme ? '#10b981' : '#ef4444';
        $emoji    = $confirme ? '✅' : '❌';
        $titre    = $confirme ? 'BILLET VALIDE' : 'BILLET INVALIDE';
        $nom      = $reservation->getUser()->getPrenom() . ' ' . $reservation->getUser()->getNom();
        $event    = $reservation->getEvenement()->getNom();
        $places   = $reservation->getNbPlaces();
        $id       = str_pad((string) $reservation->getId(), 4, '0', STR_PAD_LEFT);

        $html = "<!DOCTYPE html>
<html lang='fr'>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <title>AfkArt - Vérification</title>
  <style>
    body { margin:0; background:#111; display:flex; align-items:center; justify-content:center; min-height:100vh; font-family:sans-serif; }
    .card { background:#1c1c1c; border:2px solid {$couleur}; border-radius:20px; padding:40px 30px; text-align:center; max-width:360px; width:90%; }
    .emoji { font-size:80px; }
    .titre { color:{$couleur}; font-size:1.5rem; font-weight:bold; margin:15px 0; }
    .info { color:#ccc; margin:8px 0; font-size:1rem; }
    .accent { color:#C4704B; font-weight:bold; }
    .id { color:#555; font-size:0.75rem; margin-top:20px; font-family:monospace; }
  </style>
</head>
<body>
  <div class='card'>
    <div class='emoji'>{$emoji}</div>
    <div class='titre'>{$titre}</div>
    <div class='info'>{$nom}</div>
    <div class='info accent'>{$event}</div>
    <div class='info'>Places : {$places}</div>
    <div class='id'>BILLET #AFK-{$id}</div>
  </div>
</body>
</html>";

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    #[Route('/', name: 'app_reservation_index', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository, EvenementRepository $evenementRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        if (!$user instanceof User) {
            return $this->redirectToRoute('home');
        }

        // Automatic Cleanup (3h rule)
        $reservationRepository->cancelExpiredReservations();

        $artisanIdentifier = trim(sprintf('%s %s', (string) $user->getPrenom(), (string) $user->getNom()));
        $artisanIdentifier = $artisanIdentifier !== '' ? $artisanIdentifier : $user->getUserIdentifier();

        if ($user->getRole() === User::ROLE_ARTISAN) {
            $events = $evenementRepository->findBy(['artisan' => $artisanIdentifier], ['dateDebut' => 'DESC']);

            return $this->render('reservation/index.html.twig', [
                'events' => $events,
                'isArtisan' => true,
                'stats' => $reservationRepository->getStatsForArtisan($artisanIdentifier),
            ]);
        }

        // Handle Client View
        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservationRepository->findBy(['user' => $user], ['id' => 'DESC']),
            'availableEvents' => $evenementRepository->findAll(), // Placeholder for exploration
            'isArtisan' => false
        ]);
    }

    #[Route('/new', name: 'app_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EvenementRepository $evenementRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $reservation = new Reservation();
        $reservation->setDateReservation(new \DateTime());
        $reservation->setStatut('en_attente');
        $reservation->setUser($user instanceof User ? $user : null);

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

            if ($reservation->getStatut() === 'confirme') {
                return $this->redirectToRoute('app_reservation_payment', ['id' => $reservation->getId()]);
            }

            $this->addFlash('success', 'Réservation mise en attente. Notez bien : sans confirmation sous 3h (Paiement), elle sera annulée.');
            return $this->redirectToRoute('app_reservation_index');
        }

        return $this->render('reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/payment', name: 'app_reservation_payment', methods: ['GET', 'POST'])]
    public function payment(Reservation $reservation, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $errors = [];
            $cardHolder = trim((string) $request->request->get('card_holder'));
            $cardNumber = str_replace(' ', '', (string) $request->request->get('card_number'));
            $expiryDate = trim((string) $request->request->get('expiry_date'));
            $cvc = trim((string) $request->request->get('cvc'));

            // Validation PHP (Contrôle de saisie strict)
            if (empty($cardHolder)) {
                $errors['card_holder'] = 'Le nom du détenteur est obligatoire.';
            } elseif (strlen($cardHolder) < 3) {
                 $errors['card_holder'] = 'Le nom doit contenir au moins 3 caractères.';
            }

            if (empty($cardNumber)) {
                $errors['card_number'] = 'Le numéro de carte est obligatoire.';
            } elseif (!preg_match('/^\d{16}$/', $cardNumber)) {
                $errors['card_number'] = 'Numéro de carte invalide (16 chiffres requis).';
            }

            if (empty($expiryDate)) {
                 $errors['expiry_date'] = 'La date d\'expiration est requise.';
            } elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiryDate)) {
                $errors['expiry_date'] = 'Format invalide (MM/YY).';
            } else {
                // Check if expired
                $currentDate = new \DateTime();
                $expDate = \DateTime::createFromFormat('m/y', $expiryDate);
                // Set to last day of month
                if ($expDate) {
                    $expDate->modify('last day of this month');
                    if ($expDate < $currentDate) {
                        $errors['expiry_date'] = 'Votre carte a expiré.';
                    }
                }
            }

            if (empty($cvc)) {
                $errors['cvc'] = 'Le code CVC est requis.';
            } elseif (!preg_match('/^\d{3,4}$/', $cvc)) {
                $errors['cvc'] = 'CVC invalide (3 ou 4 chiffres).';
            }

            // Si erreurs, on réaffiche le formulaire avec les erreurs
            if (!empty($errors)) {
                return $this->render('reservation/payment.html.twig', [
                    'reservation' => $reservation,
                    'errors' => $errors,
                    'last_inputs' => $request->request->all()
                ]);
            }

            // Si tout est bon, on confirme
            $reservation->setStatut('confirme');
            $entityManager->flush();

            $this->addFlash('success', 'Paiement validé avec succès !');
            return $this->redirectToRoute('app_reservation_show', ['id' => $reservation->getId()]);
        }

        return $this->render('reservation/payment.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}', name: 'app_reservation_show', methods: ['GET'])]
    public function show(Reservation $reservation, QRCodeService $qrCodeService, Request $request): Response
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

        $qrUrl = $this->generateUrl('app_reservation_verify', ['id' => $reservation->getId()], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

        // Fix pour le scan local : si une URL de base est définie dans le .env, on l'utilise
        $customBaseUrl = $_ENV['QR_CODE_BASE_URL'] ?? null;
        if ($customBaseUrl && $customBaseUrl !== 'http://localhost:8000') {
            $qrUrl = str_replace($request->getSchemeAndHttpHost(), rtrim($customBaseUrl, '/'), $qrUrl);
        }

        $qrCode = $qrCodeService->generate($qrUrl, "Billet #" . $reservation->getId());

        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
            'qrCode' => $qrCode,
        ]);
    }

    #[Route('/{id}/download-ticket', name: 'app_reservation_download_ticket', methods: ['GET'])]
    public function downloadTicket(Reservation $reservation, QRCodeService $qrCodeService, Request $request): Response
    {
        $user = $this->getUser();
        if ($reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $qrUrl = $this->generateUrl('app_reservation_verify', ['id' => $reservation->getId()], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);

        // Fix pour le scan local
        $customBaseUrl = $_ENV['QR_CODE_BASE_URL'] ?? null;
        if ($customBaseUrl && $customBaseUrl !== 'http://localhost:8000') {
            $qrUrl = str_replace($request->getSchemeAndHttpHost(), rtrim($customBaseUrl, '/'), $qrUrl);
        }

        $qrCode = $qrCodeService->generate($qrUrl, "Billet #" . $reservation->getId());

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($pdfOptions);

        $html = $this->renderView('reservation/ticket.html.twig', [
            'reservation' => $reservation,
            'qrCode' => $qrCode,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="billet-afkart-' . $reservation->getId() . '.pdf"',
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
