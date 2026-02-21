<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\TurnstileVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PasswordResetController extends AbstractController
{
    #[Route('/forgot-password/sent', name: 'app_forgot_password_sent', methods: ['GET'])]
    public function forgotPasswordSent(): Response
    {
        return $this->render('security/forgot_password_sent.html.twig');
    }

    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        TurnstileVerifier $turnstileVerifier
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('forgot_password', (string) $request->request->get('_csrf_token', ''))) {
                $this->addFlash('error', 'Requete invalide.');
                return $this->redirectToRoute('app_forgot_password');
            }
            if (!$turnstileVerifier->verifyRequest($request)) {
                $this->addFlash('error', 'Verification CAPTCHA invalide.');
                return $this->redirectToRoute('app_forgot_password');
            }

            $email = trim((string) $request->request->get('email', ''));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $user = $userRepository->findOneBy(['email' => $email]);
                if ($user instanceof User && $user->getDeletedAt() === null) {
                    $plainToken = bin2hex(random_bytes(32));
                    $user
                        ->setResetTokenHash(hash('sha256', $plainToken))
                        ->setResetTokenCreatedAt(new \DateTimeImmutable())
                        ->setResetTokenExpiresAt((new \DateTimeImmutable())->modify('+15 minutes'))
                        ->setResetTokenRequestIp($request->getClientIp());

                    $em->flush();

                    $resetLink = $this->generateUrl(
                        'app_reset_password',
                        ['token' => $plainToken],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );

                    $from = getenv('MAIL_FROM') ?: 'noreply@afkart.tn';
                    $message = (new Email())
                        ->from($from)
                        ->to($user->getEmail())
                        ->subject('Reinitialisation du mot de passe')
                        ->text("Cliquez sur ce lien pour reinitialiser votre mot de passe (15 min): $resetLink")
                        ->html(
                            '<div style="font-family: Outfit, Arial, sans-serif; background:#FAF7F2; padding:24px;">'
                            . '<div style="max-width:560px; margin:0 auto; background:#fff; border-radius:16px; padding:28px; box-shadow:0 10px 30px rgba(0,0,0,0.08);">'
                            . '<h1 style="margin:0 0 8px 0; font-family:\'Cormorant Garamond\', Georgia, serif; color:#3D3229; font-weight:600;">'
                            . 'AfkArt · Reinitialisation'
                            . '</h1>'
                            . '<p style="margin:0 0 16px 0; color:#5C4A3D;">'
                            . 'Vous avez demande la reinitialisation de votre mot de passe.'
                            . '</p>'
                            . '<div style="padding:12px 16px; background:#FAF7F2; border-radius:12px; text-align:center;">'
                            . '<a href="' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block; background:#C4704B; color:#fff; text-decoration:none; padding:10px 18px; border-radius:999px; font-weight:600;">'
                            . 'Reinitialiser mon mot de passe'
                            . '</a>'
                            . '</div>'
                            . '<p style="margin:16px 0 0 0; color:#5C4A3D; font-size:14px;">'
                            . 'Ce lien expire dans 15 minutes.'
                            . '</p>'
                            . '</div>'
                            . '</div>'
                        );

                    try {
                        $mailer->send($message);
                    } catch (\Throwable) {
                        // Keep generic response to avoid email enumeration.
                    }
                }
            }

            return $this->redirectToRoute('app_forgot_password_sent');
        }

        return $this->render('security/forgot_password.html.twig', [
            'turnstile_site_key' => $turnstileVerifier->getSiteKey(),
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        string $token,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $userRepository->findOneByValidResetToken($token);
        if (!$user instanceof User) {
            $this->addFlash('error', 'Lien invalide ou expire.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('reset_password', (string) $request->request->get('_csrf_token', ''))) {
                $this->addFlash('error', 'Requete invalide.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            $password = (string) $request->request->get('password', '');
            $passwordConfirm = (string) $request->request->get('password_confirm', '');

            if ($password === '' || $passwordConfirm === '') {
                $this->addFlash('error', 'Tous les champs sont obligatoires.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            if ($password !== $passwordConfirm) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/', $password)) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins une majuscule, un chiffre et un symbole.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            $user->setMotdepasse(password_hash($password, PASSWORD_BCRYPT));
            $user
                ->setResetTokenHash(null)
                ->setResetTokenCreatedAt(null)
                ->setResetTokenExpiresAt(null)
                ->setResetTokenRequestIp(null);

            $em->flush();

            $this->addFlash('success', 'Mot de passe mis a jour. Vous pouvez vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
        ]);
    }
}
