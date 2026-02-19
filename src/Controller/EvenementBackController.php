<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\User;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/back/evenement')]
final class EvenementBackController extends AbstractController
{
    use BackModuleAccessTrait;

    #[Route('/', name: 'back_evenement_index', methods: ['GET'])]
    public function index(Request $request, EvenementRepository $evenementRepository): Response
    {
        $check = $this->checkModuleAccess($request, 'evenement');
        if ($check) return $check;

        $search = $request->query->get('q');
        $sort = $request->query->get('sort');
        $order = $request->query->get('order', 'DESC');
        $evenements = $evenementRepository->findBySearchAndSort($search, $sort, $order);
        $stats = $evenementRepository->getStats();
        $capacityData = $evenementRepository->getCapacityData();

        return $this->render('admin/evenement/index.html.twig', [
            'evenements' => $evenements,
            'stats' => $stats,
            'capacityData' => $capacityData,
        ]);
    }

    #[Route('/pdf', name: 'back_evenement_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, EvenementRepository $evenementRepository): Response
    {
        $check = $this->checkModuleAccess($request, 'evenement');
        if ($check) return $check;

        $evenements = $evenementRepository->findAll();
        $html = $this->renderView('admin/evenement/pdf.html.twig', ['evenements' => $evenements]);
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="evenements_' . date('Y-m-d_H-i-s') . '.pdf"',
        ]);
    }

    #[Route('/new', name: 'back_evenement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $check = $this->checkModuleAccess($request, 'evenement');
        if ($check) return $check;
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRole() !== User::ROLE_ARTISAN) {
            $this->addFlash('error', 'Seuls les artisans peuvent créer un événement.');
            return $this->redirectToRoute('back_evenement_index', [], Response::HTTP_SEE_OTHER);
        }

        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($evenement);
            $entityManager->flush();
            $this->addFlash('success', 'Événement créé.');
            return $this->redirectToRoute('back_evenement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/evenement/new.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'back_evenement_show', methods: ['GET'])]
    public function show(Request $request, Evenement $evenement): Response
    {
        $check = $this->checkModuleAccess($request, 'evenement');
        if ($check) return $check;

        return $this->render('admin/evenement/show.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    #[Route('/{id}/edit', name: 'back_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        $check = $this->checkModuleAccess($request, 'evenement');
        if ($check) return $check;
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRole() !== User::ROLE_ARTISAN) {
            $this->addFlash('error', 'Seuls les artisans peuvent modifier un événement.');
            return $this->redirectToRoute('back_evenement_index', [], Response::HTTP_SEE_OTHER);
        }

        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Événement mis à jour.');
            return $this->redirectToRoute('back_evenement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/evenement/edit.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'back_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        $check = $this->checkModuleAccess($request, 'evenement');
        if ($check) return $check;
        $user = $this->getUser();
        if (!$user instanceof User || $user->getRole() !== User::ROLE_ARTISAN) {
            $this->addFlash('error', 'Seuls les artisans peuvent supprimer un événement.');
            return $this->redirectToRoute('back_evenement_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($this->isCsrfTokenValid('delete' . $evenement->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($evenement);
            $entityManager->flush();
            $this->addFlash('success', 'Événement supprimé.');
        }
        return $this->redirectToRoute('back_evenement_index', [], Response::HTTP_SEE_OTHER);
    }
}
