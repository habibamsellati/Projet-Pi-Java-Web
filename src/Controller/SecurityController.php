<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/confirm', name: 'app_confirm')]
    public function confirm(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    ): Response {
        $session = $request->getSession();
        $email = (string) $session->get('email_confirmation_email', '');
        $codeSession = (string) $session->get('email_confirmation_code', '');
        $createdAt = (int) $session->get('email_confirmation_created', 0);

        if ($email === '' || $codeSession === '') {
            $this->addFlash('error', 'Aucune confirmation en attente.');
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $codeInput = trim((string) $request->request->get('code', ''));

            if ($createdAt > 0 && (time() - $createdAt) > 600) {
                $session->remove('email_confirmation_code');
                $session->remove('email_confirmation_created');
                $this->addFlash('error', 'Code expirÃ©. Veuillez vous reconnecter pour recevoir un nouveau code.');
                return $this->redirectToRoute('app_login');
            }

            if ($codeInput !== $codeSession) {
                $this->addFlash('error', 'Code incorrect.');
                return $this->redirectToRoute('app_confirm');
            }

            $user = $userRepository->findOneBy(['email' => $email]);
            if (!$user) {
                $this->addFlash('error', 'Utilisateur introuvable.');
                return $this->redirectToRoute('app_login');
            }

            $user->setStatut('actif');
            $em->flush();

            $session->remove('email_confirmation_pending');
            $session->remove('email_confirmation_email');
            $session->remove('email_confirmation_code');
            $session->remove('email_confirmation_created');

            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $tokenStorage->setToken($token);
            $session->set('_security_main', serialize($token));

            $this->addFlash('success', 'Email confirmÃ©. Connexion rÃ©ussie.');
            return $this->redirectToRoute('home');
        }

        return $this->render('security/confirm.html.twig', [
            'email' => $email,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(Request $request): Response
    {
        $this->addFlash('success', 'Vous êtes bien déconnecté');
        return $this->redirectToRoute('home');
    }
}
