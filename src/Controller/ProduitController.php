<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/produit')]
final class ProduitController extends AbstractController
{
    #[Route(name: 'app_produit_index', methods: ['GET'])]
    public function index(ProduitRepository $produitRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $role = strtoupper((string) $user->getRole());
        $produits = ($role === 'CLIENT')
            ? $produitRepository->findByAddedBy($user)
            : $produitRepository->findAll();
        return $this->render('produit/index.html.twig', [
            'produits' => $produits,
            'can_manage' => $this->canManageProduit(),
        ]);
    }

    #[Route('/new', name: 'app_produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        if (!$this->canManageProduit()) {
            $this->addFlash('error', 'Seuls les clients peuvent ajouter des produits.');
            return $this->redirectToRoute('app_produit_index');
        }
        $produit = new Produit();
        $produit->setAddedBy($this->getUser());
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($produit);
            $entityManager->flush();
            $this->addFlash('success', 'Produit créé avec succès.');
            return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_produit_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Produit $produit): Response
    {
        $this->assertCanViewProduit($produit);
        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
            'can_manage' => $this->canManageProduit() && $this->isOwnerProduit($produit),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_produit_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        $this->assertCanManageProduit($produit);
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Produit mis à jour.');
            return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_produit_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        $this->assertCanManageProduit($produit);
        if ($this->isCsrfTokenValid('delete' . $produit->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($produit);
            $entityManager->flush();
            $this->addFlash('success', 'Produit supprimé.');
        }
        return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
    }

    private function canManageProduit(): bool
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }
        $role = strtoupper((string) $user->getRole());
        return $role === 'CLIENT' || $role === 'ADMIN';
    }

    private function isOwnerProduit(Produit $produit): bool
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }
        $owner = $produit->getAddedBy();
        return $owner && (int) $owner->getId() === (int) $user->getId();
    }

    private function assertCanViewProduit(Produit $produit): void
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }
        $role = strtoupper((string) $user->getRole());
        if ($role === 'ADMIN') {
            return;
        }
        if ($role === 'CLIENT' && !$this->isOwnerProduit($produit)) {
            throw $this->createAccessDeniedException('Vous ne pouvez voir que vos propres produits.');
        }
    }

    private function assertCanManageProduit(Produit $produit): void
    {
        if (!$this->canManageProduit()) {
            throw $this->createAccessDeniedException('Seuls les clients peuvent modifier ou supprimer des produits.');
        }
        $user = $this->getUser();
        $role = strtoupper((string) $user->getRole());
        if ($role === 'ADMIN') {
            return;
        }
        if (!$this->isOwnerProduit($produit)) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres produits.');
        }
    }
}
