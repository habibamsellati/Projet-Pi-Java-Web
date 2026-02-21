<?php

namespace App\Controller;

use App\Entity\SuiviLivraison;
use App\Form\SuiviLivraisonType;
use App\Repository\SuiviLivraisonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/back/suivi_livraison', name: 'back_suivi_livraison_')]
class SuiviLivraisonBackController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, SuiviLivraisonRepository $repository): Response
    {
        $order = strtoupper($request->query->get('order', 'ASC'));
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'ASC';
        }
        $suivis = $repository->findBy([], ['datesuivi' => $order]);

        return $this->render('suivi_livraison/indexback.html.twig', [
            'suivis' => $suivis,
            'current_order' => $order,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $suivi = new SuiviLivraison();
        $form = $this->createForm(SuiviLivraisonType::class, $suivi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($suivi);
            $em->flush();
            $this->addFlash('success', 'Suivi ajouté avec succès');
            return $this->redirectToRoute('back_suivi_livraison_index');
        }

        return $this->render('suivi_livraison/newback.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/show', name: 'show', methods: ['GET'])]
    public function show(SuiviLivraison $suivi): Response
    {
        return $this->render('suivi_livraison/showback.html.twig', [
            'suivi' => $suivi,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SuiviLivraison $suivi, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(SuiviLivraisonType::class, $suivi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Suivi modifié avec succès');
            return $this->redirectToRoute('back_suivi_livraison_index');
        }

        return $this->render('suivi_livraison/editback.html.twig', [
            'form' => $form,
            'suivi' => $suivi,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, SuiviLivraison $suivi, EntityManagerInterface $em): Response
    {
        $token = $request->request->get('_token');
        if ($token && $this->isCsrfTokenValid('delete' . $suivi->getId(), $token)) {
            $em->remove($suivi);
            $em->flush();
            $this->addFlash('success', 'Suivi supprimé avec succès');
        }
        return $this->redirectToRoute('back_suivi_livraison_index');
    }

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(SuiviLivraisonRepository $repo): Response
    {
        $results = $repo->createQueryBuilder('s')
            ->select('s.etat AS etat, COUNT(s.id) AS total')
            ->groupBy('s.etat')
            ->getQuery()
            ->getResult();

        $labels = [];
        $data = [];
        foreach ($results as $row) {
            $labels[] = $row['etat'];
            $data[] = (int) $row['total'];
        }

        return $this->render('suivi_livraison/statistiquessuivilivraison.html.twig', [
            'labels' => $labels,
            'data' => $data,
        ]);
    }

    #[Route('/pdf', name: 'pdf', methods: ['GET'])]
    public function exportPdf(SuiviLivraisonRepository $suiviLivraisonRepository): Response
    {
        $suivisLivraison = $suiviLivraisonRepository->findAll();
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $html = $this->renderView('suivi_livraison/pdf.html.twig', [
            'suivisLivraison' => $suivisLivraison,
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="suivi_livraisons.pdf"',
        ]);
    }
}
