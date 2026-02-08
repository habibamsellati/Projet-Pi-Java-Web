<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;

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
        if (!$this->getUser() || $this->getUser()->getRole() !== 'ADMIN') {
            $this->addFlash('error', 'Accès réservé aux administrateurs');
            return $this->redirectToRoute('home');
        }
        return null;
    }

    private function isAdminUser(): bool
    {
        return $this->getUser() && $this->getUser()->getRole() === 'ADMIN';
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

        $admin = $this->getUser();
        $user = $userRepository->find($id);
        
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('back_users');
        }

        // Empêcher la suppression de soi-même
        if ($user->getId() === $admin->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas vous supprimer vous-même');
            return $this->redirectToRoute('back_users');
        }

        // Vérifier le mot de passe admin
        $adminPassword = $request->request->get('admin_password', '');
        if (empty($adminPassword) || !password_verify($adminPassword, $admin->getMotdepasse())) {
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
        if ($this->isAdminUser()) {
            return new JsonResponse(['ok' => true, 'bypass' => true]);
        }

        $module = (string) $request->request->get('module', '');
        $username = trim((string) $request->request->get('username', ''));
        $password = (string) $request->request->get('password', '');

        if (!array_key_exists($module, self::RESPONSABLE_ACCOUNTS)) {
            return new JsonResponse(['ok' => false, 'message' => 'Module non valide'], Response::HTTP_BAD_REQUEST);
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
        $check = $this->checkModuleAccess($request, 'produit_recyclable');
        if ($check) return $check;

        return $this->render('admin/produits/index.html.twig');
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

        return $this->render('admin/evenements/index.html.twig');
    }

    #[Route('/reclamations', name: 'back_reclamations')]
    public function reclamations(Request $request): Response
    {
        $check = $this->checkModuleAccess($request, 'reclamation');
        if ($check) return $check;

        return $this->render('admin/reclamations/index.html.twig');
    }

    #[Route('/articles', name: 'back_articles')]
    public function articles(Request $request): Response
    {
        $check = $this->checkModuleAccess($request, 'article');
        if ($check) return $check;

        return $this->render('admin/articles/index.html.twig');
    }
}
