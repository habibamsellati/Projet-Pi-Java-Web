<?php

namespace App\Controller;

use App\Entity\Livraison;
use App\Form\LivraisonType;
use App\Repository\LivraisonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/back/livraison', name: 'back_livraison_')]
class LivraisonBackController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, LivraisonRepository $livraisonRepository): Response
    {
        $order = strtoupper($request->query->get('order', 'ASC'));
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'ASC';
        }
        $livraisons = $livraisonRepository->findBy([], ['datelivraison' => $order]);

        return $this->render('livraison/indexback.html.twig', [
            'livraisons' => $livraisons,
            'current_order' => $order,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $livraison = new Livraison();
        $form = $this->createForm(LivraisonType::class, $livraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($livraison);
            $em->flush();
            $this->addFlash('success', 'Livraison créée.');
            return $this->redirectToRoute('back_livraison_index');
        }

        return $this->render('livraison/newback.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/edit/{id}', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Livraison $livraison, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(LivraisonType::class, $livraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Livraison modifiée.');
            return $this->redirectToRoute('back_livraison_index');
        }

        return $this->render('livraison/editback.html.twig', [
            'form' => $form,
            'livraison' => $livraison,
        ]);
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Livraison $livraison, EntityManagerInterface $em): Response
    {
        $em->remove($livraison);
        $em->flush();
        $this->addFlash('success', 'Livraison supprimée.');
        return $this->redirectToRoute('back_livraison_index');
    }

    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(Livraison $livraison): Response
    {
        return $this->render('livraison/showback.html.twig', [
            'livraison' => $livraison,
        ]);
    }

    #[Route('/statistiques', name: 'stats', methods: ['GET'])]
    public function stats(LivraisonRepository $livraisonRepository): Response
    {
        return $this->render('livraison/statistiques.html.twig', [
            'stats' => $livraisonRepository->countByAdresse(),
        ]);
    }

    #[Route('/trier', name: 'trier', methods: ['GET'])]
    public function trier(Request $request, LivraisonRepository $livraisonRepository): Response
    {
        $order = strtoupper($request->query->get('order', 'ASC'));
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'ASC';
        }
        $livraisons = $livraisonRepository->findBy([], ['datelivraison' => $order]);
        return $this->render('livraison/indexback.html.twig', [
            'livraisons' => $livraisons,
            'current_order' => $order,
        ]);
    }

    #[Route('/pdf', name: 'pdf', methods: ['GET'])]
    public function pdf(LivraisonRepository $livraisonRepository): Response
    {
        $livraisons = $livraisonRepository->findBy([], ['datelivraison' => 'DESC']);
        $html = $this->renderView('livraison/pdf.html.twig', ['livraisons' => $livraisons]);
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="livraisons.pdf"',
        ]);
    }
}
