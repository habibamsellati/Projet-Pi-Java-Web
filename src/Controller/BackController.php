<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Commande;
use App\Entity\Commentaire;
use App\Entity\Produit;
use App\Entity\Proposition;
use App\Entity\Reclamation;
use App\Entity\ReponseReclamation;
use App\Entity\User;
use App\Form\ProduitType;
use App\Form\PropositionType;
use App\Form\ReponseReclamationType;
use App\Repository\ArticleRepository;
use App\Repository\CommandeRepository;
use App\Repository\ProduitRepository;
use App\Repository\PropositionRepository;
use App\Repository\ReclamationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/back')]
final class BackController extends AbstractController
{
    private const ADMIN_ROLES = ['CLIENT', 'ARTISANT', 'ADMIN', 'LIVREUR'];
    private const RESPONSABLE_ACCOUNTS = [
        'produit_recyclable' => ['username' => 'produit', 'password' => 'recyclable', 'label' => 'Produit Recyclable'],
        'livraison' => ['username' => 'livraison', 'password' => 'livreur', 'label' => 'Livraison'],
        'evenement' => ['username' => 'evenement', 'password' => 'reservation', 'label' => 'Evenement'],
        'reclamation' => ['username' => 'reclamation', 'password' => 'reponse', 'label' => 'Reclamation'],
        'article' => ['username' => 'article', 'password' => 'oeuvres', 'label' => 'Article'],
    ];

    private function checkAdminAccess(): ?Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'ADMIN') {
            $this->addFlash('error', 'Accès réservé aux administrateurs');
            return $this->redirectToRoute('home');
        }
        return null;
    }

    private function isAdminUser(): bool
    {
        /** @var User|null $user */
        $user = $this->getUser();
        return $user !== null && $user->getRole() === 'ADMIN';
    }

    private function checkModuleAccess(Request $request, string $module): ?Response
    {
        if ($this->isAdminUser()) {
            return null;
        }

        $access = (array) $request->getSession()->get('responsable_access', []);
        if (!empty($access[$module])) {
            return null;
        }

        $this->addFlash('error', 'Acces refuse. Veuillez vous authentifier.');
        return $this->redirectToRoute('back');
    }

    #[Route('/verify-admin', name: 'verify_admin', methods: ['POST'])]
    public function verifyAdmin(Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est connecté et est admin
        if (!$user || $user->getRole() !== 'ADMIN') {
            $this->addFlash('error', 'Vous n\'êtes pas un admin');
            return $this->redirectToRoute('home');
        }
        
        $password = $request->request->get('admin_password', '');
        
        // Vérifier le mot de passe
        if (!password_verify($password, $user->getMotdepasse())) {
            $this->addFlash('error', 'Mot de passe incorrect');
            return $this->redirectToRoute('home');
        }
        
        // Stocker la vérification en session
        $request->getSession()->set('admin_verified', true);
        $request->getSession()->set('admin_verified_at', time());
        
        // Mot de passe correct, redirection vers le dashboard
        return $this->redirectToRoute('back_users');
    }

    #[Route('/verify-front', name: 'verify_front', methods: ['POST'])]
    public function verifyFront(Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        if (!$user || $user->getRole() !== 'ADMIN') {
            $this->addFlash('error', 'Accès refusé');
            return $this->redirectToRoute('home');
        }
        
        $password = $request->request->get('admin_password', '');
        
        if (!password_verify($password, $user->getMotdepasse())) {
            $this->addFlash('error', 'Mot de passe incorrect');
            return $this->redirectToRoute('back');
        }
        
        return $this->redirectToRoute('home');
    }

    #[Route('/statistiques', name: 'back_statistiques', methods: ['GET'])]
    public function statistiques(
        ArticleRepository $articleRepository,
        CommandeRepository $commandeRepository,
        ReclamationRepository $reclamationRepository,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        $articleCount = $articleRepository->count([]);
        $commandeCount = $commandeRepository->count([]);
        $reclamationCount = $reclamationRepository->count([]);
        $commentaireCount = $em->getRepository(Commentaire::class)->count([]);

        $statsArticlesByCategorie = $articleRepository->getStatsByCategorie();
        $topArtisans = $articleRepository->getTopArtisans(5);

        $statsCommandesByStatut = $commandeRepository->getStatsByStatut();
        $totalRevenue = $commandeRepository->getTotalRevenue();

        $statsReclamationsByStatut = $reclamationRepository->getStatsByStatut();

        $usersByRole = $userRepository->countByRole();
        $artisansCount = $usersByRole[User::ROLE_ARTISAN] ?? 0;
        $clientsCount = $usersByRole[User::ROLE_CLIENT] ?? 0;

        return $this->render('admin/statistiques.html.twig', [
            'articleCount' => $articleCount,
            'commandeCount' => $commandeCount,
            'reclamationCount' => $reclamationCount,
            'commentaireCount' => $commentaireCount,
            'statsArticlesByCategorie' => $statsArticlesByCategorie,
            'topArtisans' => $topArtisans,
            'statsCommandesByStatut' => $statsCommandesByStatut,
            'totalRevenue' => $totalRevenue,
            'statsReclamationsByStatut' => $statsReclamationsByStatut,
            'artisansCount' => $artisansCount,
            'clientsCount' => $clientsCount,
        ]);
    }

    #[Route('', name: 'back')]
    public function index(UserRepository $userRepository): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        $users = $userRepository->searchAndSort(null, 'datecreation', 'DESC', false);
        return $this->render('admin/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/utilisateurs', name: 'back_users')]
    public function users(Request $request, UserRepository $userRepository): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        $query = trim((string) $request->query->get('q', ''));
        $sort = (string) $request->query->get('sort', 'datecreation');
        $dir = (string) $request->query->get('dir', 'DESC');

        $users = $userRepository->searchAndSort($query !== '' ? $query : null, $sort, $dir, false);

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'q' => $query,
            'sort' => $sort,
            'dir' => strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC',
        ]);
    }

    #[Route('/utilisateurs/export/pdf', name: 'back_users_export_pdf')]
    public function exportUsersPdf(UserRepository $userRepository): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        $users = $userRepository->searchAndSort(null, 'datecreation', 'DESC', false);

        $html = $this->renderView('admin/users/export-pdf.html.twig', [
            'users' => $users,
        ]);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="utilisateurs_' . date('Y-m-d_H-i-s') . '.pdf"',
        ]);
    }

    #[Route('/utilisateurs/ajouter', name: 'back_user_create', methods: ['GET', 'POST'])]
    public function createUser(Request $request, EntityManagerInterface $em): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom', '');
            $prenom = $request->request->get('prenom', '');
            $email = $request->request->get('email', '');
            $motdepasse = $request->request->get('motdepasse', '');
            $role = strtoupper($request->request->get('role', ''));
            
            // Validation
            if (empty($nom) || empty($prenom) || empty($email) || empty($motdepasse) || empty($role)) {
                $this->addFlash('error', 'Tous les champs sont obligatoires');
                return $this->redirectToRoute('back_user_create');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Email invalide (il faut un @)');
                return $this->redirectToRoute('back_user_create');
            }

            if (!in_array($role, self::ADMIN_ROLES)) {
                $this->addFlash('error', 'Rôle non valide');
                return $this->redirectToRoute('back_user_create');
            }

            // Vérifier que l'email n'existe pas
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $this->addFlash('error', 'Cet email existe déjà');
                return $this->redirectToRoute('back_user_create');
            }

            $user = new User();
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setMotdepasse(password_hash($motdepasse, PASSWORD_BCRYPT));
            $user->setRole($role);
            $user->setStatut('actif');
            $user->setDatecreation(new \DateTime());

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès');
            return $this->redirectToRoute('back_users');
        }

        return $this->render('admin/users/create.html.twig', [
            'roles' => self::ADMIN_ROLES,
        ]);
    }

    #[Route('/utilisateurs/{id}/modifier', name: 'back_user_edit', methods: ['GET', 'POST'])]
    public function editUser(int $id, UserRepository $userRepository, Request $request, EntityManagerInterface $em): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        $user = $userRepository->find($id);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('back_users');
        }

        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom', '');
            $prenom = $request->request->get('prenom', '');
            $email = $request->request->get('email', '');
            $motdepasse = $request->request->get('motdepasse', '');
            $role = strtoupper($request->request->get('role', ''));
            $statut = $request->request->get('statut', '');

            // Validation
            if (empty($nom) || empty($prenom) || empty($email) || empty($role) || empty($statut)) {
                $this->addFlash('error', 'Tous les champs sont obligatoires');
                return $this->redirectToRoute('back_user_edit', ['id' => $id]);
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Email invalide (il faut un @)');
                return $this->redirectToRoute('back_user_edit', ['id' => $id]);
            }

            if (!in_array($role, self::ADMIN_ROLES)) {
                $this->addFlash('error', 'Rôle non valide');
                return $this->redirectToRoute('back_user_edit', ['id' => $id]);
            }

            // Vérifier que l'email n'existe pas ailleurs
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser && $existingUser->getId() !== $id) {
                $this->addFlash('error', 'Cet email existe déjà');
                return $this->redirectToRoute('back_user_edit', ['id' => $id]);
            }

            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setRole($role);
            $user->setStatut($statut);
            
            // Mise à jour du mot de passe seulement s'il est fourni
            if (!empty($motdepasse)) {
                $user->setMotdepasse(password_hash($motdepasse, PASSWORD_BCRYPT));
            }

            $em->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès');
            return $this->redirectToRoute('back_users');
        }

        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'roles' => self::ADMIN_ROLES,
        ]);
    }

    #[Route('/utilisateurs/{id}/supprimer', name: 'back_user_delete', methods: ['POST'])]
    public function deleteUser(int $id, UserRepository $userRepository, Request $request, EntityManagerInterface $em): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        /** @var User|null $admin */
        $admin = $this->getUser();
        $user = $userRepository->find($id);
        
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('back_users');
        }

        // Empêcher la suppression de soi-même
        if ($admin && $user->getId() === $admin->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas vous supprimer vous-même');
            return $this->redirectToRoute('back_users');
        }

        // Vérifier le mot de passe admin
        $adminPassword = $request->request->get('admin_password', '');
        if (!$admin || empty($adminPassword) || !password_verify($adminPassword, $admin->getMotdepasse())) {
            $this->addFlash('error', 'Mot de passe administrateur incorrect');
            return $this->redirectToRoute('back_users');
        }

        $user->setDeletedAt(new \DateTime());
        $user->setStatut('supprime');
        $em->flush();

        $this->addFlash('success', 'Utilisateur supprimé avec succès (corbeille)');
        return $this->redirectToRoute('back_users');
    }
    #[Route('/utilisateurs/supprimes', name: 'back_users_deleted')]
    public function usersDeleted(UserRepository $userRepository): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        $users = $userRepository->findDeleted();

        return $this->render('admin/users/deleted.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/utilisateurs/{id}/restaurer', name: 'back_user_restore', methods: ['POST'])]
    public function restoreUser(int $id, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        $user = $userRepository->find($id);
        if (!$user || $user->getDeletedAt() === null) {
            $this->addFlash('error', 'Utilisateur non trouvÃ©');
            return $this->redirectToRoute('back_users_deleted');
        }

        $user->setDeletedAt(null);
        $user->setStatut('actif');
        $em->flush();

        $this->addFlash('success', 'Utilisateur restaurÃ© avec succÃ¨s');
        return $this->redirectToRoute('back_users_deleted');
    }

    #[Route('/access/verify', name: 'back_access_verify', methods: ['POST'])]
    public function verifyResponsableAccess(Request $request, UserRepository $userRepository, EntityManagerInterface $em): JsonResponse
    {
        $module = (string) $request->request->get('module', '');
        $username = trim((string) $request->request->get('username', ''));
        $password = (string) $request->request->get('password', '');

        if (!array_key_exists($module, self::RESPONSABLE_ACCOUNTS)) {
            return new JsonResponse(['ok' => false, 'message' => 'Module non valide'], Response::HTTP_BAD_REQUEST);
        }

        // Admin credentials can open any module
        $admin = $userRepository->findOneBy(['email' => $username, 'role' => 'ADMIN']);
        if ($admin && password_verify($password, $admin->getMotdepasse())) {
            return new JsonResponse(['ok' => true]);
        }

        $expected = self::RESPONSABLE_ACCOUNTS[$module];
        if ($username !== $expected['username']) {
            return new JsonResponse(['ok' => false, 'message' => 'Identifiants incorrects'], Response::HTTP_FORBIDDEN);
        }

        $account = $userRepository->findOneBy(['email' => $expected['username']]);
        if (!$account) {
            $account = new User();
            $account->setNom($expected['label']);
            $account->setPrenom('Responsable');
            $account->setEmail($expected['username']);
            $account->setMotdepasse(password_hash($expected['password'], PASSWORD_BCRYPT));
            $account->setRole('RESPONSABLE');
            $account->setStatut('actif');
            $account->setDatecreation(new \DateTime());
            $em->persist($account);
            $em->flush();
        }

        if (!password_verify($password, $account->getMotdepasse())) {
            return new JsonResponse(['ok' => false, 'message' => 'Identifiants incorrects'], Response::HTTP_FORBIDDEN);
        }

        $session = $request->getSession();
        $access = (array) $session->get('responsable_access', []);
        $access[$module] = true;
        $session->set('responsable_access', $access);

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/produits', name: 'back_produits')]
    public function produits(Request $request): Response
    {
        return $this->redirectToRoute('back_produit_recyclable');
    }

    #[Route('/produit-recyclable', name: 'back_produit_recyclable')]
    public function produitRecyclable(Request $request, ProduitRepository $produitRepository): Response
    {
        $check = $this->checkModuleAccess($request, 'produit_recyclable');
        if ($check) return $check;

        $searchQuery = $request->query->get('search_query', '');
        $etatFilter = $request->query->get('etat', '');
        $sort = $request->query->get('sort');
        $orderBy = [];
        if ($sort === 'date') {
            $orderBy = ['dateajout' => 'DESC'];
        }
        $produits = $produitRepository->findBySearch($searchQuery, $orderBy, $etatFilter);
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
            'sort' => $sort,
        ]);
    }

    #[Route('/produit/export-pdf', name: 'back_produit_export_pdf')]
    public function exportProduitPdf(ProduitRepository $produitRepository): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;
        $produits = $produitRepository->findBy([], ['dateajout' => 'DESC']);
        $html = $this->renderView('admin/export_produits.html.twig', ['produits' => $produits]);
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return new Response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="produits_' . date('Y-m-d_H-i-s') . '.pdf"',
        ]);
    }

    #[Route('/proposition/export-pdf', name: 'back_proposition_export_pdf')]
    public function exportPropositionPdf(PropositionRepository $propositionRepository): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;
        $propositions = $propositionRepository->findBy([], ['date' => 'DESC']);
        $html = $this->renderView('admin/export_propositions.html.twig', ['propositions' => $propositions]);
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return new Response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="propositions_' . date('Y-m-d_H-i-s') . '.pdf"',
        ]);
    }

    #[Route('/produit/new', name: 'back_produit_new', methods: ['GET', 'POST'])]
    public function newProduit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $check = $this->checkModuleAccess($request, 'produit_recyclable');
        if ($check) return $check;
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($produit);
            $entityManager->flush();
            $this->addFlash('success', 'Produit créé.');
            return $this->redirectToRoute('back_produit_recyclable');
        }
        return $this->render('admin/new_produit.html.twig', ['produit' => $produit, 'form' => $form]);
    }

    #[Route('/produit/{id}/edit', name: 'back_produit_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function editProduit(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        $check = $this->checkModuleAccess($request, 'produit_recyclable');
        if ($check) return $check;
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Produit mis à jour.');
            return $this->redirectToRoute('back_produit_recyclable');
        }
        return $this->render('admin/edit_produit.html.twig', ['produit' => $produit, 'form' => $form]);
    }

    #[Route('/produit/{id}/delete-recyclable', name: 'back_recyclable_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteProduit(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        $check = $this->checkModuleAccess($request, 'produit_recyclable');
        if ($check) return $check;
        if ($this->isCsrfTokenValid('delete' . $produit->getId(), (string) $request->request->get('_token'))) {
            if (!$produit->getPropositions()->isEmpty()) {
                $this->addFlash('error', 'Impossible de supprimer ce produit : il possède des propositions associées.');
                return $this->redirectToRoute('back_produit_recyclable');
            }
            $entityManager->remove($produit);
            $entityManager->flush();
            $this->addFlash('success', 'Produit supprimé.');
        }
        return $this->redirectToRoute('back_produit_recyclable');
    }

    #[Route('/propositions', name: 'back_propositions')]
    public function propositions(Request $request, PropositionRepository $propositionRepository): Response
    {
        $check = $this->checkModuleAccess($request, 'produit_recyclable');
        if ($check) return $check;
        $searchProduit = $request->query->get('search_produit', '');
        $searchEtat = $request->query->get('search_etat', '');
        $sort = $request->query->get('sort');
        $orderBy = [];
        if ($sort === 'date') {
            $orderBy = ['date' => 'DESC'];
        }
        $propositions = $propositionRepository->findByProduitAndEtat($searchProduit, $searchEtat, $orderBy);
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

    #[Route('/proposition/new', name: 'back_proposition_new', methods: ['GET', 'POST'])]
    public function newProposition(Request $request, EntityManagerInterface $entityManager): Response
    {
        $check = $this->checkModuleAccess($request, 'produit_recyclable');
        if ($check) return $check;
        $proposition = new Proposition();
        $user = $this->getUser();
        if ($user) {
            $proposition->setUser($user);
        }
        $form = $this->createForm(PropositionType::class, $proposition);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$proposition->getUser() && $user) {
                $proposition->setUser($user);
            }
            $entityManager->persist($proposition);
            $entityManager->flush();
            $this->addFlash('success', 'Proposition créée.');
            return $this->redirectToRoute('back_propositions');
        }
        return $this->render('admin/new_proposition.html.twig', ['proposition' => $proposition, 'form' => $form]);
    }

    #[Route('/proposition/{id}/edit', name: 'back_proposition_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function editProposition(Request $request, Proposition $proposition, EntityManagerInterface $entityManager): Response
    {
        $check = $this->checkModuleAccess($request, 'produit_recyclable');
        if ($check) return $check;
        $form = $this->createForm(PropositionType::class, $proposition);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Proposition mise à jour.');
            return $this->redirectToRoute('back_propositions');
        }
        return $this->render('admin/edit_proposition.html.twig', ['proposition' => $proposition, 'form' => $form]);
    }

    #[Route('/proposition/{id}/delete', name: 'back_proposition_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteProposition(Request $request, Proposition $proposition, EntityManagerInterface $entityManager): Response
    {
        $check = $this->checkModuleAccess($request, 'produit_recyclable');
        if ($check) return $check;
        if ($this->isCsrfTokenValid('delete' . $proposition->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($proposition);
            $entityManager->flush();
            $this->addFlash('success', 'Proposition supprimée.');
        } else {
            $this->addFlash('error', 'Jeton invalide.');
        }
        return $this->redirectToRoute('back_propositions');
    }

    #[Route('/produit/statistiques', name: 'back_produit_stats')]
    public function statsProduit(ProduitRepository $produitRepository): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;
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

    #[Route('/proposition/statistiques', name: 'back_proposition_stats')]
    public function statsProposition(PropositionRepository $propositionRepository): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;
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

    #[Route('/statistiques-produits', name: 'back_stats_produits')]
    public function statsProduitsPropositions(ProduitRepository $produitRepository, PropositionRepository $propositionRepository): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;
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
        return $this->render('admin/stats_produits.html.twig', [
            'total_produits' => count($allProduits),
            'total_propositions' => count($allPropositions),
            'stats_etat' => $statsEtat,
            'stats_materiau' => $statsMateriau,
            'stats_prop_etat' => $statsPropEtat,
        ]);
    }

    #[Route('/livraisons', name: 'back_livraisons')]
    public function livraisons(Request $request): Response
    {
        $check = $this->checkModuleAccess($request, 'livraison');
        if ($check) return $check;

        return $this->render('admin/livraisons/index.html.twig');
    }

    #[Route('/evenements', name: 'back_evenements')]
    public function evenements(Request $request): Response
    {
        $check = $this->checkModuleAccess($request, 'evenement');
        if ($check) return $check;

        return $this->redirectToRoute('back_evenement_index');
    }

    #[Route('/articles', name: 'back_articles')]
    public function articles(Request $request, ArticleRepository $articleRepository): Response
    {
        $check = $this->checkModuleAccess($request, 'article');
        if ($check) return $check;

        $search = $request->query->get('recherche', '');
        $tri = $request->query->get('tri', 'date_desc');

        $articles = $articleRepository->searchWithFilters(
            $search !== '' ? $search : null,
            null,
            null,
            in_array($tri, ['date_desc', 'date_asc', 'titre_asc', 'titre_desc'], true) ? $tri : 'date_desc'
        );

        return $this->render('admin/articles_back.html.twig', [
            'articles' => $articles,
            'filters' => ['recherche' => $search, 'tri' => $tri],
        ]);
    }

    #[Route('/article/{id}/supprimer', name: 'back_article_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteArticle(Article $article, Request $request, EntityManagerInterface $entityManager): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        $token = $request->request->get('_token');
        if ($token && $this->isCsrfTokenValid('back_article_delete' . $article->getId(), $token)) {
            $entityManager->remove($article);
            $entityManager->flush();
            $this->addFlash('success', 'L\'article a été supprimé.');
        } else {
            $this->addFlash('error', 'Token invalide.');
        }
        return $this->redirectToRoute('back_articles', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/commentaire/{id}/supprimer', name: 'back_comment_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteComment(Commentaire $commentaire, Request $request, EntityManagerInterface $entityManager): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        $token = $request->request->get('_token');
        if ($token && $this->isCsrfTokenValid('back_comment_delete' . $commentaire->getId(), $token)) {
            $entityManager->remove($commentaire);
            $entityManager->flush();
            $this->addFlash('success', 'Le commentaire a été supprimé.');
        } else {
            $this->addFlash('error', 'Token invalide.');
        }
        return $this->redirectToRoute('back_articles', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/commandes', name: 'back_commandes', methods: ['GET'])]
    public function commandes(Request $request, CommandeRepository $commandeRepository): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        $search = $request->query->get('recherche', '');
        $statut = $request->query->get('statut', '');
        $commandes = $commandeRepository->searchWithFilters(
            $search !== '' ? $search : null,
            $statut !== '' ? $statut : null
        );

        return $this->render('admin/commandes_back.html.twig', [
            'commandes' => $commandes,
            'filters' => ['recherche' => $search, 'statut' => $statut],
        ]);
    }

    #[Route('/liste-validee', name: 'back_liste_validee', methods: ['GET'])]
    public function listeValidee(CommandeRepository $commandeRepository): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        $commandes = $commandeRepository->findBy(['statut' => 'valide'], ['datecommande' => 'DESC']);

        return $this->render('admin/liste_validee_back.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/commande/{id}/valider', name: 'back_commande_valider', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function validerCommande(Commande $commande, Request $request, EntityManagerInterface $entityManager): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        $token = $request->request->get('_token');
        if ($token && $this->isCsrfTokenValid('back_commande_valider' . $commande->getId(), $token)) {
            $commande->setStatut('valide');
            $entityManager->flush();
            $this->addFlash('success', 'Commande validée.');
        } else {
            $this->addFlash('error', 'Token invalide.');
        }
        return $this->redirectToRoute('back_commandes', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/commande/{id}/invalider', name: 'back_commande_invalider', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function invaliderCommande(Commande $commande, Request $request, EntityManagerInterface $entityManager): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        $token = $request->request->get('_token');
        if ($token && $this->isCsrfTokenValid('back_commande_invalider' . $commande->getId(), $token)) {
            $commande->setStatut('invalide');
            $entityManager->flush();
            $this->addFlash('success', 'Commande invalidée.');
        } else {
            $this->addFlash('error', 'Token invalide.');
        }
        return $this->redirectToRoute('back_commandes', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/commandes/pdf', name: 'back_commandes_pdf', methods: ['GET'])]
    public function commandesPdf(Request $request, CommandeRepository $commandeRepository): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        $search = $request->query->get('recherche', '');
        $statut = $request->query->get('statut', '');
        $commandes = $commandeRepository->searchWithFilters(
            $search !== '' ? $search : null,
            $statut !== '' ? $statut : null
        );

        $html = $this->renderView('admin/commandes_pdf.html.twig', ['commandes' => $commandes]);
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="commandes.pdf"',
        ]);
    }

    #[Route('/reclamations', name: 'back_reclamations')]
    public function reclamations(Request $request, ReclamationRepository $reclamationRepository): Response
    {
        $check = $this->checkModuleAccess($request, 'reclamation');
        if ($check) return $check;

        $search = $request->query->get('recherche', '');
        $statut = $request->query->get('statut', '');
        $tri = $request->query->get('tri', 'date_desc');

        $reclamations = $reclamationRepository->searchWithFilters(
            $search !== '' ? $search : null,
            $statut !== '' ? $statut : null,
            in_array($tri, ['date_desc', 'date_asc', 'titre_asc', 'titre_desc'], true) ? $tri : 'date_desc',
            null
        );

        return $this->render('admin/reclamations_back.html.twig', [
            'reclamations' => $reclamations,
            'filters' => ['recherche' => $search, 'statut' => $statut, 'tri' => $tri],
        ]);
    }

    #[Route('/reclamations/pdf', name: 'back_reclamations_pdf', methods: ['GET'])]
    public function reclamationsPdf(Request $request, ReclamationRepository $reclamationRepository): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        $search = $request->query->get('recherche', '');
        $statut = $request->query->get('statut', '');
        $tri = $request->query->get('tri', 'date_desc');

        $reclamations = $reclamationRepository->searchWithFilters(
            $search !== '' ? $search : null,
            $statut !== '' ? $statut : null,
            in_array($tri, ['date_desc', 'date_asc', 'titre_asc', 'titre_desc'], true) ? $tri : 'date_desc',
            null
        );

        $html = $this->renderView('admin/reclamations_pdf.html.twig', ['reclamations' => $reclamations]);
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="reclamations.pdf"',
        ]);
    }

    #[Route('/reclamation/{id}', name: 'back_reclamation_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function showReclamation(Reclamation $reclamation): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        return $this->render('admin/reclamation_show.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/reclamation/{id}/repondre', name: 'back_reclamation_repondre', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function repondreReclamation(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        /** @var User|null $admin */
        $admin = $this->getUser();
        if (!$admin || $admin->getRole() !== User::ROLE_ADMIN) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('back_reclamations');
        }

        $reponse = new ReponseReclamation();
        $reponse->setReclamation($reclamation);
        $reponse->setAdmin($admin);
        $reponse->setDatereponse(new \DateTime());

        $form = $this->createForm(ReponseReclamationType::class, $reponse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($reclamation->getStatut() === 'en_attente') {
                $reclamation->setStatut('en_cours');
            }
            $entityManager->persist($reponse);
            $entityManager->flush();
            $this->addFlash('success', 'Votre réponse a été enregistrée.');
            return $this->redirectToRoute('back_reclamation_show', ['id' => $reclamation->getId()]);
        }

        return $this->render('admin/reclamation_repondre.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
        ]);
    }

    #[Route('/reclamation/{id}/valider', name: 'back_reclamation_valider', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function validerReclamation(Reclamation $reclamation, Request $request, EntityManagerInterface $entityManager): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        $token = $request->request->get('_token');
        if ($token && $this->isCsrfTokenValid('back_reclamation_valider' . $reclamation->getId(), $token)) {
            $reclamation->setStatut('validee');
            $entityManager->flush();
            $this->addFlash('success', 'Réclamation validée.');
        } else {
            $this->addFlash('error', 'Token invalide.');
        }
        return $this->redirectToRoute('back_reclamation_show', ['id' => $reclamation->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/reclamation/{id}/rejeter', name: 'back_reclamation_rejeter', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function rejeterReclamation(Reclamation $reclamation, Request $request, EntityManagerInterface $entityManager): Response
    {
        $check = $this->checkAdminAccess();
        if ($check) return $check;

        $token = $request->request->get('_token');
        if ($token && $this->isCsrfTokenValid('back_reclamation_rejeter' . $reclamation->getId(), $token)) {
            $reclamation->setStatut('rejetee');
            $entityManager->flush();
            $this->addFlash('success', 'Réclamation rejetée.');
        } else {
            $this->addFlash('error', 'Token invalide.');
        }
        return $this->redirectToRoute('back_reclamation_show', ['id' => $reclamation->getId()], Response::HTTP_SEE_OTHER);
    }
}
