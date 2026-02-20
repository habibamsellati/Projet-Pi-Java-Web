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

#[Route('/livraison')]
class LivraisonController extends AbstractController
{
    #[Route('/', name: 'app_livraison_index', methods: ['GET'])]
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
        return $this->render('livraison/index.html.twig', [
            'livraisons' => $livraisons,
        ]);
    }

    #[Route('/new', name: 'app_livraison_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        $livraison = new Livraison();
        $livraison->setStatutlivraison('en_attente');

        $form = $this->createForm(LivraisonType::class, $livraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($livraison);
            $em->flush();
            $this->addFlash('success', 'La livraison a été créée avec succès.');
            return $this->redirectToRoute('app_livraison_index');
        }

        return $this->render('livraison/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_livraison_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Livraison $livraison): Response
    {
        $this->assertCanAccessLivraison($livraison);
        return $this->render('livraison/show.html.twig', [
            'livraison' => $livraison,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_livraison_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Livraison $livraison, EntityManagerInterface $em): Response
    {
        $this->assertCanAccessLivraison($livraison);
        $form = $this->createForm(LivraisonType::class, $livraison);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Mise à jour effectuée.');
            return $this->redirectToRoute('app_livraison_index');
        }

        return $this->render('livraison/edit.html.twig', [
            'livraison' => $livraison,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_livraison_delete', methods: ['POST'])]
    public function delete(Request $request, Livraison $livraison, EntityManagerInterface $em): Response
    {
        $this->assertCanAccessLivraison($livraison);
        $token = $request->request->get('_token');
        if ($token && $this->isCsrfTokenValid('delete' . $livraison->getId(), $token)) {
            $em->remove($livraison);
            $em->flush();
            $this->addFlash('success', 'Livraison supprimée.');
        }
        return $this->redirectToRoute('app_livraison_index');
    }

    #[Route('/{id}/noter', name: 'app_suivi_livraison_note', methods: ['GET', 'POST'])]
    public function noter(Request $request, Livraison $livraison, EntityManagerInterface $entityManager): Response
    {
        $this->assertCanAccessLivraison($livraison);
        if ($request->isMethod('POST')) {
            $note = $request->request->get('note');
            if ($note !== null && $note !== '') {
                $livraison->setNoteLivreur((int) $note);
                $entityManager->flush();
                $this->addFlash('success', 'Merci ! Note de ' . $note . '/5 enregistrée.');
            }
            return $this->redirectToRoute('app_livraison_show', ['id' => $livraison->getId()]);
        }
        return $this->render('livraison/noter.html.twig', [
            'livraison' => $livraison,
        ]);
    }

    private function assertCanAccessLivraison(Livraison $livraison): void
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }
        $role = strtoupper((string) $user->getRole());
        if ($role === 'ADMIN' || $role === 'LIVREUR') {
            return;
        }
        $commande = $livraison->getCommande();
        if (!$commande || $commande->getClient()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez accéder qu’à vos propres livraisons.');
        }
    }
}
