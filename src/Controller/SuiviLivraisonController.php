<?php

namespace App\Controller;

use App\Entity\SuiviLivraison;
use App\Form\SuiviLivraisonType;
use App\Repository\SuiviLivraisonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
<<<<<<< HEAD
=======
use App\Repository\LivraisonRepository; // N'oubliez pas l'import
>>>>>>> master

#[Route('/suivi/livraison')]
final class SuiviLivraisonController extends AbstractController
{
    #[Route(name: 'app_suivi_livraison_index', methods: ['GET'])]
<<<<<<< HEAD
    public function index(SuiviLivraisonRepository $suiviLivraisonRepository): Response
    {
        return $this->render('suivi_livraison/index.html.twig', [
            'suivi_livraisons' => $suiviLivraisonRepository->findAll(),
        ]);
    }

=======
    public function index(LivraisonRepository $livraisonRepository): Response
    {
        // On envoie 'livraisons' à Twig pour correspondre à votre boucle {% for livraison in livraisons %}
        return $this->render('suivi_livraison/index.html.twig', [
            'livraisons' => $livraisonRepository->findAll(),
        ]);
    }
>>>>>>> master
    #[Route('/new', name: 'app_suivi_livraison_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $suiviLivraison = new SuiviLivraison();
        $form = $this->createForm(SuiviLivraisonType::class, $suiviLivraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($suiviLivraison);
            $entityManager->flush();

            return $this->redirectToRoute('app_suivi_livraison_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('suivi_livraison/new.html.twig', [
            'suivi_livraison' => $suiviLivraison,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_suivi_livraison_show', methods: ['GET'])]
    public function show(SuiviLivraison $suiviLivraison): Response
    {
        return $this->render('suivi_livraison/show.html.twig', [
            'suivi_livraison' => $suiviLivraison,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_suivi_livraison_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SuiviLivraison $suiviLivraison, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SuiviLivraisonType::class, $suiviLivraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_suivi_livraison_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('suivi_livraison/edit.html.twig', [
            'suivi_livraison' => $suiviLivraison,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_suivi_livraison_delete', methods: ['POST'])]
    public function delete(Request $request, SuiviLivraison $suiviLivraison, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$suiviLivraison->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($suiviLivraison);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_suivi_livraison_index', [], Response::HTTP_SEE_OTHER);
    }
<<<<<<< HEAD
}
=======
    
}
>>>>>>> master
