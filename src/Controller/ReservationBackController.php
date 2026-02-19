<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\User;
use App\Form\ReservationType;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/back/reservation')]
final class ReservationBackController extends AbstractController
{
    use BackModuleAccessTrait;

    #[Route('/pdf', name: 'back_reservation_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, ReservationRepository $reservationRepository): Response
    {
        $check = $this->checkModuleAccess($request, 'evenement');
        if ($check) return $check;

        $reservations = $reservationRepository->findAll();
        $html = $this->renderView('admin/reservation/pdf.html.twig', ['reservations' => $reservations]);
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reservations_' . date('Y-m-d_H-i-s') . '.pdf"',
        ]);
    }

    #[Route('/', name: 'back_reservation_index', methods: ['GET'])]
    public function index(Request $request, ReservationRepository $reservationRepository): Response
    {
        $check = $this->checkModuleAccess($request, 'evenement');
        if ($check) return $check;

        $user = $this->getUser();
        if ($user instanceof User && $user->getRole() === User::ROLE_ARTISAN) {
            $this->addFlash('info', 'En tant qu\'artisan, consultez les réservations depuis l\'espace public.');
            return $this->redirectToRoute('app_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        $search = $request->query->get('q');
        $sort = $request->query->get('sort');
        $order = $request->query->get('order', 'DESC');
        $reservations = $reservationRepository->findBySearchAndSort($search, $sort, $order);
        $stats = $reservationRepository->getStats();

        return $this->render('admin/reservation/index.html.twig', [
            'reservations' => $reservations,
            'stats' => $stats,
        ]);
    }

    #[Route('/new', name: 'back_reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $check = $this->checkModuleAccess($request, 'evenement');
        if ($check) return $check;

        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation créée.');
            return $this->redirectToRoute('back_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/reservation/new.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'back_reservation_show', methods: ['GET'])]
    public function show(Request $request, Reservation $reservation): Response
    {
        $check = $this->checkModuleAccess($request, 'evenement');
        if ($check) return $check;

        return $this->render('admin/reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/edit', name: 'back_reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $check = $this->checkModuleAccess($request, 'evenement');
        if ($check) return $check;

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Réservation mise à jour.');
            return $this->redirectToRoute('back_reservation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'back_reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $check = $this->checkModuleAccess($request, 'evenement');
        if ($check) return $check;

        if ($this->isCsrfTokenValid('delete' . $reservation->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($reservation);
            $entityManager->flush();
            $this->addFlash('success', 'Réservation supprimée.');
        }
        return $this->redirectToRoute('back_reservation_index', [], Response::HTTP_SEE_OTHER);
    }
}
