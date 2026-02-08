<?php

namespace App\Controller;

use App\Entity\Livraison;
use App\Form\LivraisonType;
use App\Repository\LivraisonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\GeoService;
use Dompdf\Dompdf;
use Dompdf\Options;


#[Route('/livraison')]
final class LivraisonController extends AbstractController
{
    #[Route(name: 'app_livraison_index', methods: ['GET'])]
    public function index(LivraisonRepository $livraisonRepository): Response
    {
        return $this->render('livraison/index.html.twig', [
            'livraisons' => $livraisonRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_livraison_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $livraison = new Livraison();
        $form = $this->createForm(LivraisonType::class, $livraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($livraison);
            $entityManager->flush();

            return $this->redirectToRoute('app_livraison_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('livraison/new.html.twig', [
            'livraison' => $livraison,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_livraison_show', methods: ['GET'])]
    public function show(Livraison $livraison): Response
    {
        return $this->render('livraison/show.html.twig', [
            'livraison' => $livraison,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_livraison_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Livraison $livraison, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LivraisonType::class, $livraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_livraison_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('livraison/edit.html.twig', [
            'livraison' => $livraison,
            'form' => $form,
        ]);
    }

   #[Route('/{id}', name: 'app_livraison_delete', methods: ['POST'])]
public function delete(Request $request, Livraison $livraison, EntityManagerInterface $entityManager): Response
{
    // On supprime directement la livraison sans contrôle CSRF
    $entityManager->remove($livraison);
    $entityManager->flush();

    return $this->redirectToRoute('app_livraison_index', [], Response::HTTP_SEE_OTHER);
}  
 #[Route('/back/livraison/statistiques', name: 'back_livraison_stats')]
    public function stats(LivraisonRepository $livraisonRepository): Response
    {
        return $this->render('livraison/statistiques.html.twig', [
            'stats' => $livraisonRepository->countByAdresse(),
        ]);
    }
    
    #[Route('/back/livraison/pdf', name: 'back_livraison_pdf')]
    public function exportPdf(LivraisonRepository $livraisonRepository): Response
    {
        $livraisons = $livraisonRepository->findAll();

        // Options Dompdf
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans'); // Pour les accents français

        $dompdf = new Dompdf($options);

        $html = $this->renderView('livraison/pdf.html.twig', [
            'livraisons' => $livraisons,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="livraisons.pdf"',
            ]
        );
    }
    #[Route('/{id}/map', name: 'livraison_map')]
public function map(Livraison $livraison, GeoService $geoService): Response
{
    // Utiliser le getter pour récupérer l'adresse
    $coordinates = $geoService->getCoordinates($livraison->getAddresslivraison());

    return $this->render('livraison/map.html.twig', [
        'livraison' => $livraison,
        'coordinates' => $coordinates
    ]);
}

    
    
    
    
    
    
    
    
}


