<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(Request $request, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $hasProfileFields = $request->request->has('nom')
                || $request->request->has('prenom')
                || $request->request->has('email');

            $nom = trim((string) $request->request->get('nom', $user->getNom() ?? ''));
            $prenom = trim((string) $request->request->get('prenom', $user->getPrenom() ?? ''));
            $email = trim((string) $request->request->get('email', $user->getEmail() ?? ''));

            $password = (string) $request->request->get('password', '');
            $passwordConfirm = (string) $request->request->get('password_confirm', '');

            if ($hasProfileFields) {
                if ($nom === '' || strlen($nom) > 15 || !preg_match('/^[a-zA-ZÀ-ÿ\s-]+$/', $nom)) {
                    $this->addFlash('error', 'Nom invalide (1-15 lettres).');
                    return $this->redirectToRoute('app_profile');
                }
                if ($prenom === '' || strlen($prenom) > 30 || !preg_match('/^[a-zA-ZÀ-ÿ\s-]+$/', $prenom)) {
                    $this->addFlash('error', 'Prenom invalide (1-30 lettres).');
                    return $this->redirectToRoute('app_profile');
                }
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->addFlash('error', 'Email invalide.');
                    return $this->redirectToRoute('app_profile');
                }

                $existing = $userRepository->findOneBy(['email' => $email]);
                if ($existing instanceof User && $existing->getId() !== $user->getId()) {
                    $this->addFlash('error', 'Cet email est deja utilise.');
                    return $this->redirectToRoute('app_profile');
                }

                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setEmail($email);
            }

            if ($password === '' || $passwordConfirm === '') {
                if ($hasProfileFields) {
                    $em->flush();
                    $this->addFlash('success', 'Profil mis a jour.');
                }
                return $this->redirectToRoute('app_profile');
            }
            if ($password !== $passwordConfirm) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_profile');
            }
            if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/', $password)) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins une majuscule, un chiffre et un symbole.');
                return $this->redirectToRoute('app_profile');
            }

            $user->setMotdepasse(password_hash($password, PASSWORD_BCRYPT));
            $em->flush();
            $this->addFlash('success', 'Profil et mot de passe mis a jour.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('front/profile.html.twig', [
            'user' => $user,
        ]);
    }
}
