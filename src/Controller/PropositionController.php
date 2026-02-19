<?php

namespace App\Controller;

use App\Entity\Proposition;
use App\Form\PropositionType;
use App\Repository\PropositionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/proposition')]
final class PropositionController extends AbstractController
{
    #[Route(name: 'app_proposition_index', methods: ['GET'])]
    public function index(PropositionRepository $propositionRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        $role = strtoupper((string) $user->getRole());
        $propositions = ($role === 'CLIENT')
            ? $propositionRepository->findForClient($user)
            : $propositionRepository->findAll();
        return $this->render('proposition/index.html.twig', [
            'propositions' => $propositions,
            'can_manage' => $this->canManageProposition(),
        ]);
    }

    #[Route('/new', name: 'app_proposition_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        $proposition = new Proposition();
        $proposition->setUser($this->getUser());
        $form = $this->createForm(PropositionType::class, $proposition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$proposition->getUser()) {
                $proposition->setUser($this->getUser());
            }
            $entityManager->persist($proposition);
            $entityManager->flush();
            $this->addFlash('success', 'La proposition a été ajoutée avec succès !');
            return $this->redirectToRoute('app_proposition_index', [], Response::HTTP_SEE_OTHER);
        }
        if ($form->isSubmitted()) {
            $this->addFlash('error', 'Il y a des erreurs dans le formulaire.');
        }

        return $this->render('proposition/new.html.twig', [
            'proposition' => $proposition,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_proposition_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Proposition $proposition): Response
    {
        $this->assertCanViewProposition($proposition);
        return $this->render('proposition/show.html.twig', [
            'proposition' => $proposition,
            'can_manage' => $this->canManageProposition(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_proposition_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Proposition $proposition, EntityManagerInterface $entityManager): Response
    {
        $this->assertCanManageProposition($proposition);
        $form = $this->createForm(PropositionType::class, $proposition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Proposition mise à jour.');
            return $this->redirectToRoute('app_proposition_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('proposition/edit.html.twig', [
            'proposition' => $proposition,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_proposition_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Proposition $proposition, EntityManagerInterface $entityManager): Response
    {
        $this->assertCanManageProposition($proposition);
        if ($this->isCsrfTokenValid('delete' . $proposition->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($proposition);
            $entityManager->flush();
            $this->addFlash('success', 'Proposition supprimée.');
        }
        return $this->redirectToRoute('app_proposition_index', [], Response::HTTP_SEE_OTHER);
    }

    private function canManageProposition(): bool
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }
        $role = strtoupper((string) $user->getRole());
        return $role === 'ARTISANT' || $role === 'ADMIN';
    }

    private function assertCanViewProposition(Proposition $proposition): void
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }
        $role = strtoupper((string) $user->getRole());
        if ($role === 'ADMIN' || $role === 'ARTISANT') {
            return;
        }
        if ($role === 'CLIENT') {
            if ($proposition->getUser()?->getId() === $user->getId()) {
                return;
            }
            $produit = $proposition->getProduit();
            if (!$produit || $produit->getAddedBy()?->getId() !== $user->getId()) {
                throw $this->createAccessDeniedException('Vous ne pouvez voir que vos propositions ou celles liées à vos produits.');
            }
        }
    }

    private function assertCanManageProposition(Proposition $proposition): void
    {
        if (!$this->canManageProposition()) {
            throw $this->createAccessDeniedException('Seuls les artisans peuvent modifier ou supprimer des propositions.');
        }
    }
}
