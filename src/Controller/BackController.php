<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Entity\Proposition;
use App\Form\ProduitType;
use App\Form\PropositionType;
use App\Repository\ProduitRepository;
use App\Repository\PropositionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BackController extends AbstractController
{
    #[Route('/back', name: 'back')]
    public function index(): Response
    {
        // Tableau d'utilisateurs fictifs
        $users = [
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@mail.com', 'active' => true],
            ['id' => 2, 'name' => 'Bob', 'email' => 'bob@mail.com', 'active' => false],
            ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@mail.com', 'active' => true],
        ];

        return $this->render('admin/index.html.twig', [
            'users' => $users, // on passe la variable à Twig
        ]);
    }

    #[Route('/back/produit-recyclable', name: 'admin_produit_recyclable')]
    public function produitRecyclable(Request $request, ProduitRepository $produitRepository): Response
    {
        $searchQuery = $request->query->get('search_query', '');
        $etatFilter = $request->query->get('etat', '');
        $sort = $request->query->get('sort');
        $orderBy = [];
        
        if ($sort === 'date') {
            $orderBy = ['dateajout' => 'DESC'];
        }
        
        $produits = $produitRepository->findBySearch($searchQuery, $orderBy, $etatFilter);
        
        // Stats sur TOUS les produits pour la modale
        $allProduits = $produitRepository->findAll();
        $statsEtat = [];
        $statsMateriau = [];
        foreach ($allProduits as $p) {
            $etat = $p->getEtat() ?: 'Non spécifié';
            $statsEtat[$etat] = ($statsEtat[$etat] ?? 0) + 1;
            
            $mat = $p->getTypemateriau() ?: 'Inconnu';
            $statsMateriau[$mat] = ($statsMateriau[$mat] ?? 0) + 1;
        }
        
        return $this->render('admin/produit_recyclable.html.twig', [
            'produits' => $produits,
            'stats_etat' => $statsEtat,
            'stats_materiau' => $statsMateriau,
            'total_produits' => count($allProduits),
            'search_query' => $searchQuery,
            'search_etat' => $etatFilter,
        ]);
    }

    #[Route('/back/produit/export-pdf', name: 'admin_produit_export_pdf')]
    public function exportPdf(ProduitRepository $produitRepository): Response
    {
        $produits = $produitRepository->findBy([], ['dateajout' => 'DESC']);
        
        return $this->render('admin/export_produits.html.twig', [
            'produits' => $produits,
        ]);
    }

    #[Route('/back/proposition/export-pdf', name: 'admin_proposition_export_pdf')]
    public function exportPropositionPdf(PropositionRepository $propositionRepository): Response
    {
        $propositions = $propositionRepository->findBy([], ['date' => 'DESC']);
        
        return $this->render('admin/export_propositions.html.twig', [
            'propositions' => $propositions,
        ]);
    }

    #[Route('/back/produit/new', name: 'admin_produit_new', methods: ['GET', 'POST'])]
    public function newProduit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($produit);
            $entityManager->flush();

            return $this->redirectToRoute('admin_produit_recyclable');
        }

        return $this->render('admin/new_produit.html.twig', [
            'produit' => $produit,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/back/produit/{id}/edit', name: 'admin_produit_edit', methods: ['GET', 'POST'])]
    public function editProduit(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_produit_recyclable');
        }

        return $this->render('admin/edit_produit.html.twig', [
            'produit' => $produit,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/back/produit/{id}/delete-recyclable', name: 'admin_recyclable_delete', methods: ['POST'])]
    public function deleteProduit(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->request->get('_token'))) {
            // Vérifier s'il y a des propositions liées
            if (!$produit->getPropositions()->isEmpty()) {
                $this->addFlash('error', 'Impossible de supprimer ce produit car il possède des propositions associées.');
                return $this->redirectToRoute('admin_produit_recyclable');
            }

            $entityManager->remove($produit);
            $entityManager->flush();
            $this->addFlash('success', 'Le produit a été supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_produit_recyclable');
    }

    #[Route('/back/propositions', name: 'admin_propositions')]
    public function propositions(Request $request, PropositionRepository $propositionRepository): Response
    {
        $searchProduit = $request->query->get('search_produit', '');
        $searchEtat = $request->query->get('search_etat', '');
        $sort = $request->query->get('sort');
        $orderBy = [];
        
        if ($sort === 'date') {
            $orderBy = ['date' => 'DESC'];
        }
        
        $propositions = $propositionRepository->findByProduitAndEtat($searchProduit, $searchEtat, $orderBy);
        
        // Stats sur TOUTES les propositions pour la modale
        $allPropositions = $propositionRepository->findAll();
        $statsProduit = [];
        $statsEtat = [];
        foreach ($allPropositions as $prop) {
            $nomProduit = $prop->getProduit() ? $prop->getProduit()->getNomproduit() : 'Sans produit';
            $statsProduit[$nomProduit] = ($statsProduit[$nomProduit] ?? 0) + 1;
            
            if ($prop->getProduit()) {
                $etat = $prop->getProduit()->getEtat() ?: 'Inconnu';
                $statsEtat[$etat] = ($statsEtat[$etat] ?? 0) + 1;
            }
        }
        
        return $this->render('admin/propositions.html.twig', [
            'propositions' => $propositions,
            'stats_produit' => $statsProduit,
            'stats_etat' => $statsEtat,
            'total_propositions' => count($allPropositions),
            'search_produit' => $searchProduit,
            'search_etat' => $searchEtat,
            'sort' => $sort,
        ]);
    }

    #[Route('/back/proposition/new', name: 'admin_proposition_new', methods: ['GET', 'POST'])]
    public function newProposition(Request $request, EntityManagerInterface $entityManager): Response
    {
        $proposition = new Proposition();
        $form = $this->createForm(PropositionType::class, $proposition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($proposition);
            $entityManager->flush();

            return $this->redirectToRoute('admin_propositions');
        }

        return $this->render('admin/new_proposition.html.twig', [
            'proposition' => $proposition,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/back/proposition/{id}/edit', name: 'admin_proposition_edit', methods: ['GET', 'POST'])]
    public function editProposition(Request $request, Proposition $proposition, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PropositionType::class, $proposition);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_propositions');
        }

        return $this->render('admin/edit_proposition.html.twig', [
            'proposition' => $proposition,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/back/proposition/{id}/delete', name: 'admin_proposition_delete', methods: ['POST'])]
    public function deleteProposition(Request $request, Proposition $proposition, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$proposition->getId(), $request->request->get('_token'))) {
            $entityManager->remove($proposition);
            $entityManager->flush();
            $this->addFlash('success', 'La proposition a été supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Erreur de sécurité lors de la suppression (Jeton invalide). Veuillez rafraîchir la page.');
        }

        return $this->redirectToRoute('admin_propositions');
    }

    #[Route('/back/produit/statistiques', name: 'admin_produit_stats')]
    public function statsProduit(ProduitRepository $produitRepository): Response
    {
        $allProduits = $produitRepository->findAll();
        $statsEtat = [];
        foreach ($allProduits as $p) {
            $etat = $p->getEtat() ?: 'Non spécifié';
            $statsEtat[$etat] = ($statsEtat[$etat] ?? 0) + 1;
        }

        return $this->render('admin/stats_produit.html.twig', [
            'total_produits' => count($allProduits),
            'stats_etat' => $statsEtat,
        ]);
    }

    #[Route('/back/proposition/statistiques', name: 'admin_proposition_stats')]
    public function statsProposition(PropositionRepository $propositionRepository): Response
    {
        $allPropositions = $propositionRepository->findAll();
        $statsProduit = [];
        foreach ($allPropositions as $prop) {
            $nomProduit = $prop->getProduit() ? $prop->getProduit()->getNomproduit() : 'Sans produit';
            $statsProduit[$nomProduit] = ($statsProduit[$nomProduit] ?? 0) + 1;
        }

        return $this->render('admin/stats_proposition.html.twig', [
            'total_propositions' => count($allPropositions),
            'stats_produit' => $statsProduit,
        ]);
    }

    #[Route('/back/statistiques', name: 'admin_stats')]
    public function stats(ProduitRepository $produitRepository, PropositionRepository $propositionRepository): Response
    {
        $allProduits = $produitRepository->findAll();
        $allPropositions = $propositionRepository->findAll();
        
        $statsEtat = [];
        $statsMateriau = [];
        foreach ($allProduits as $p) {
            $etat = $p->getEtat() ?: 'Non spécifié';
            $statsEtat[$etat] = ($statsEtat[$etat] ?? 0) + 1;
            
            $mat = $p->getTypemateriau() ?: 'Inconnu';
            $statsMateriau[$mat] = ($statsMateriau[$mat] ?? 0) + 1;
        }

        $statsPropEtat = [];
        foreach ($allPropositions as $prop) {
            if ($prop->getProduit()) {
                $etat = $prop->getProduit()->getEtat() ?: 'Inconnu';
                $statsPropEtat[$etat] = ($statsPropEtat[$etat] ?? 0) + 1;
            }
        }

        return $this->render('admin/stats.html.twig', [
            'total_produits' => count($allProduits),
            'total_propositions' => count($allPropositions),
            'stats_etat' => $statsEtat,
            'stats_materiau' => $statsMateriau,
            'stats_prop_etat' => $statsPropEtat,
        ]);
    }

}
