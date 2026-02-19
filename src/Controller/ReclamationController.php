<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Entity\User;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reclamation')]
final class ReclamationController extends AbstractController
{
    #[Route('', name: 'app_reclamation_index', methods: ['GET'])]
    public function index(Request $request, ReclamationRepository $reclamationRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à vos réclamations.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->getRole() !== User::ROLE_CLIENT && $user->getRole() !== User::ROLE_ARTISAN) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('home');
        }

        $search = $request->query->get('recherche', '');
        $statut = $request->query->get('statut', '');
        $tri = $request->query->get('tri', 'date_desc');

        $reclamations = $reclamationRepository->searchWithFilters(
            $search !== '' ? $search : null,
            $statut !== '' ? $statut : null,
            in_array($tri, ['date_desc', 'date_asc', 'titre_asc', 'titre_desc'], true) ? $tri : 'date_desc',
            $user->getId()
        );

        return $this->render('reclamation/index.html.twig', [
            'reclamations' => $reclamations,
            'user' => $user,
            'filters' => [
                'recherche' => $search,
                'statut' => $statut,
                'tri' => $tri,
            ],
        ]);
    }

    #[Route('/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour créer une réclamation.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->getRole() !== User::ROLE_CLIENT && $user->getRole() !== User::ROLE_ARTISAN) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('home');
        }

        $reclamation = new Reclamation();
        $reclamation->setDatecreation(new \DateTime());
        $reclamation->setStatut('en_attente');
        $reclamation->setUser($user);

        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reclamation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réclamation a été créée avec succès.');
            return $this->redirectToRoute('app_reclamation_index');
        }

        return $this->render('reclamation/new.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/{id}', name: 'app_reclamation_show', methods: ['GET'])]
    public function show(Reclamation $reclamation): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour voir cette réclamation.');
            return $this->redirectToRoute('app_login');
        }

        if ($reclamation->getUser()?->getId() !== $user->getId() && $user->getRole() !== User::ROLE_ADMIN) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cette réclamation.');
            return $this->redirectToRoute('app_reclamation_index');
        }

        return $this->render('reclamation/show.html.twig', [
            'reclamation' => $reclamation,
            'user' => $user,
            'isAdmin' => $user->getRole() === User::ROLE_ADMIN,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reclamation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour modifier une réclamation.');
            return $this->redirectToRoute('app_login');
        }

        if ($reclamation->getUser()?->getId() !== $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres réclamations.');
            return $this->redirectToRoute('app_reclamation_index');
        }

        $statutsNonModifiables = ['repondue', 'validee', 'rejetee', 'en_cours'];
        if (in_array($reclamation->getStatut(), $statutsNonModifiables)) {
            $this->addFlash('error', 'Vous ne pouvez modifier une réclamation que si elle est en attente.');
            return $this->redirectToRoute('app_reclamation_show', ['id' => $reclamation->getId()]);
        }

        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Votre réclamation a été modifiée avec succès.');
            return $this->redirectToRoute('app_reclamation_show', ['id' => $reclamation->getId()]);
        }

        return $this->render('reclamation/edit.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_reclamation_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour supprimer une réclamation.');
            return $this->redirectToRoute('app_login');
        }

        if ($reclamation->getUser()?->getId() !== $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres réclamations.');
            return $this->redirectToRoute('app_reclamation_index');
        }

        $statutsNonSupprimables = ['repondue', 'validee', 'rejetee', 'en_cours'];
        if (in_array($reclamation->getStatut(), $statutsNonSupprimables)) {
            $this->addFlash('error', 'Vous ne pouvez supprimer une réclamation que si elle est en attente.');
            return $this->redirectToRoute('app_reclamation_show', ['id' => $reclamation->getId()]);
        }

        $token = $request->request->get('_token') ?? $request->getPayload()->getString('_token');
        if ($this->isCsrfTokenValid('delete' . $reclamation->getId(), $token)) {
            $entityManager->remove($reclamation);
            $entityManager->flush();
            $this->addFlash('success', 'Votre réclamation a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
    }
}
