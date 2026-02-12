<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class UserAuthenticator extends AbstractAuthenticator
{
    private EntityManagerInterface $em;
    private RouterInterface $router;
    private MailerInterface $mailer;

    public function __construct(EntityManagerInterface $em, RouterInterface $router, MailerInterface $mailer)
    {
        $this->em = $em;
        $this->router = $router;
        $this->mailer = $mailer;
    }

    private function sendConfirmationEmail(string $toEmail, string $code): void
    {
        $from = getenv('MAIL_FROM') ?: 'noreply@afkart.tn';
        $subject = 'Code de confirmation';
        $message = 'Votre code de confirmation est : ' . $code;
        $html = '
        <div style="font-family: Outfit, Arial, sans-serif; background:#FAF7F2; padding:24px;">
            <div style="max-width:560px; margin:0 auto; background:#fff; border-radius:16px; padding:28px; box-shadow:0 10px 30px rgba(0,0,0,0.08);">
                <h1 style="margin:0 0 8px 0; font-family:\'Cormorant Garamond\', Georgia, serif; color:#3D3229; font-weight:600;">
                    AfkArt · Confirmation
                </h1>
                <p style="margin:0 0 16px 0; color:#5C4A3D;">
                    L\'art qui donne une seconde vie. Voici votre code :
                </p>
                <div style="font-size:28px; letter-spacing:4px; font-weight:700; color:#C4704B; padding:12px 16px; background:#FAF7F2; border-radius:12px; text-align:center;">
                    ' . $code . '
                </div>
                <p style="margin:16px 0 0 0; color:#5C4A3D; font-size:14px;">
                    Ce code expire dans 10 minutes.
                </p>
            </div>
        </div>';

        $email = (new Email())
            ->from($from)
            ->to($toEmail)
            ->subject($subject)
            ->text($message)
            ->html($html);

        $this->mailer->send($email);
    }

    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === '/login' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        /** @var Session $session */
        $session = $request->getSession();
        $session->remove('email_confirmation_pending');
        $session->remove('email_confirmation_email');
        $session->remove('email_confirmation_code');
        $session->remove('email_confirmation_created');

        $email = $request->request->get('_username', '');
        $password = $request->request->get('_password', '');

        if (empty($email) || empty($password)) {
            throw new AuthenticationException('Email and password required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new AuthenticationException('Email invalide (il faut un @).');
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            throw new AuthenticationException('User not found.');
        }

        if ($user->getDeletedAt() !== null) {
            throw new AuthenticationException('Compte supprimÃ©.');
        }

        if ($user->getStatut() !== 'actif') {
            $code = (string) random_int(100000, 999999);
            $session->set('email_confirmation_pending', true);
            $session->set('email_confirmation_email', $email);
            $session->set('email_confirmation_code', $code);
            $session->set('email_confirmation_created', time());

            try {
                $this->sendConfirmationEmail($email, $code);
            } catch (\Throwable $e) {
                if ($request->hasSession()) {
                    $session->getFlashBag()->add('error', 'Envoi email échoué, utilisez le code reçu si possible.');
                }
            }

            if ($request->hasSession()) {
                $session->getFlashBag()->add('error', 'Compte inactif. Un code de confirmation a été envoyé par email.');
            }
            throw new AuthenticationException('Email confirmation required.');
        }

        return new Passport(
            new UserBadge($email, function($userIdentifier) use ($user) {
                return $user;
            }),
            new PasswordCredentials($password),
            [
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var Session $session */
        $session = $request->getSession();
        $session->remove('email_confirmation_pending');
        $session->remove('email_confirmation_email');
        $session->remove('email_confirmation_code');
        $session->remove('email_confirmation_created');

        $user = $token->getUser();
        if ($user instanceof User) {
            $role = strtoupper((string) $user->getRole());
            if (str_starts_with($role, 'ROLE_')) {
                $role = substr($role, 5);
            }
            if ($role === 'ADMIN') {
                return new RedirectResponse($this->router->generate('front_admin'));
            }
            if ($role === 'CLIENT') {
                return new RedirectResponse($this->router->generate('front_client'));
            }
            if ($role === 'ARTISANT') {
                return new RedirectResponse($this->router->generate('front_artisan'));
            }
            if ($role === 'LIVREUR') {
                return new RedirectResponse($this->router->generate('front_livreur'));
            }
        }
        return new RedirectResponse($this->router->generate('home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Store error in session and redirect back to login
        /** @var Session $session */
        $session = $request->getSession();
        $session->set('_security.last_username', $request->request->get('_username'));
        if ($session->get('email_confirmation_pending')
            || str_contains($exception->getMessage(), 'Email confirmation required')) {
            return new RedirectResponse($this->router->generate('app_confirm'));
        }
        if ($request->hasSession()) {
            $session->getFlashBag()->add('error', $exception->getMessage());
        }
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
