<?php

namespace App\Controller;

use App\Entity\SuiviLivraison;
use App\Form\SuiviLivraisonType;
use App\Repository\LivraisonRepository;
use App\Repository\SuiviLivraisonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/suivi/livraison')]
final class SuiviLivraisonController extends AbstractController
{
    #[Route('', name: 'app_suivi_livraison_index', methods: ['GET'])]
    public function index(LivraisonRepository $livraisonRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $role = strtoupper((string) $user->getRole());
        $livraisons = ($role === 'ADMIN' || $role === 'LIVREUR')
            ? $livraisonRepository->findAllOrderByDateAsc()
            : $livraisonRepository->findByClient($user);
        return $this->render('suivi_livraison/index.html.twig', [
            'livraisons' => $livraisons,
        ]);
    }

    #[Route('/new', name: 'app_suivi_livraison_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        $suiviLivraison = new SuiviLivraison();
        $form = $this->createForm(SuiviLivraisonType::class, $suiviLivraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($suiviLivraison);
            $entityManager->flush();
            $this->addFlash('success', 'Suivi ajouté.');
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
        $this->assertCanAccessSuivi($suiviLivraison);
        return $this->render('suivi_livraison/show.html.twig', [
            'suivi_livraison' => $suiviLivraison,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_suivi_livraison_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SuiviLivraison $suiviLivraison, EntityManagerInterface $entityManager): Response
    {
        $this->assertCanAccessSuivi($suiviLivraison);
        $form = $this->createForm(SuiviLivraisonType::class, $suiviLivraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Suivi modifié.');
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
        $this->assertCanAccessSuivi($suiviLivraison);
        $token = $request->request->get('_token');
        if ($token && $this->isCsrfTokenValid('delete' . $suiviLivraison->getId(), $token)) {
            $entityManager->remove($suiviLivraison);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_suivi_livraison_index', [], Response::HTTP_SEE_OTHER);
    }

    private function assertCanAccessSuivi(SuiviLivraison $suiviLivraison): void
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }
        $role = strtoupper((string) $user->getRole());
        if ($role === 'ADMIN' || $role === 'LIVREUR') {
            return;
        }
        $livraison = $suiviLivraison->getLivraison();
        $commande = $livraison?->getCommande();
        if (!$commande || $commande->getClient()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez accéder qu’à vos propres suivis.');
        }
    }
}
