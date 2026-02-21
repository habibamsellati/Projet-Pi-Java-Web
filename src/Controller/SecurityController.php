<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Repository\UserRepository;
use App\Service\TurnstileVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, TurnstileVerifier $turnstileVerifier): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'turnstile_site_key' => $turnstileVerifier->getSiteKey(),
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

    #[Route('/oauth/{provider}/start', name: 'app_oauth_start', methods: ['GET'])]
    public function oauthStart(string $provider, Request $request): Response
    {
        $provider = strtolower(trim($provider));
        if (!in_array($provider, ['google', 'facebook'], true)) {
            $this->addFlash('error', 'Provider OAuth non supporte.');
            return $this->redirectToRoute('app_login');
        }

        $state = bin2hex(random_bytes(16));
        $session = $request->getSession();
        $session->set('oauth_state', $state);
        $session->set('oauth_provider', $provider);
        $session->set('oauth_popup', $request->query->getBoolean('popup', false));

        $clientId = $provider === 'google'
            ? $this->getEnvValue('GOOGLE_CLIENT_ID')
            : $this->getEnvValue('FACEBOOK_CLIENT_ID');

        if ($clientId === '') {
            if ($request->query->getBoolean('popup', false)) {
                return new Response(
                    '<!doctype html><html><head><meta charset="utf-8"><title>OAuth non configure</title></head><body style="font-family:Arial,sans-serif;padding:24px;">'
                    . '<h3>Configuration OAuth manquante</h3>'
                    . '<p>Veuillez remplir les variables .env du provider puis reessayer.</p>'
                    . '</body></html>'
                );
            }
            $this->addFlash('error', 'Configuration OAuth manquante.');
            return $this->redirectToRoute('app_login');
        }

        $redirectUri = $this->generateUrl(
            'app_oauth_callback',
            ['provider' => $provider],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        if ($provider === 'google') {
            $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
                'response_type' => 'code',
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'scope' => 'openid email profile',
                'state' => $state,
                'prompt' => 'select_account',
            ]);
        } else {
            $url = 'https://www.facebook.com/v20.0/dialog/oauth?' . http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'state' => $state,
                'scope' => 'email,public_profile',
            ]);
        }

        return $this->redirect($url);
    }

    #[Route('/oauth/{provider}/callback', name: 'app_oauth_callback', methods: ['GET'])]
    public function oauthCallback(
        string $provider,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    ): Response {
        $provider = strtolower(trim($provider));
        $session = $request->getSession();
        $stateSession = (string) $session->get('oauth_state', '');
        $providerSession = (string) $session->get('oauth_provider', '');
        $popupSession = (bool) $session->get('oauth_popup', false);
        $stateInput = (string) $request->query->get('state', '');
        $code = (string) $request->query->get('code', '');

        $session->remove('oauth_state');
        $session->remove('oauth_provider');
        $session->remove('oauth_popup');

        if ($stateSession === '' || $stateInput === '' || !hash_equals($stateSession, $stateInput) || $providerSession !== $provider) {
            $this->addFlash('error', 'Session OAuth invalide.');
            return $this->redirectToRoute('app_login');
        }

        if ($code === '') {
            $this->addFlash('error', 'Code OAuth manquant.');
            return $this->redirectToRoute('app_login');
        }

        $oauthProfile = $this->fetchOauthProfile($provider, $code);
        if ($oauthProfile === null || empty($oauthProfile['email']) || empty($oauthProfile['id'])) {
            $this->addFlash('error', 'Connexion OAuth impossible (email/profil indisponible).');
            return $this->redirectToRoute('app_login');
        }

        $email = strtolower(trim((string) $oauthProfile['email']));
        $providerId = (string) $oauthProfile['id'];
        $nom = trim((string) ($oauthProfile['nom'] ?? ''));
        $prenom = trim((string) ($oauthProfile['prenom'] ?? ''));

        $user = $userRepository->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            $user = (new User())
                ->setEmail($email)
                ->setNom($nom !== '' ? $nom : 'Utilisateur')
                ->setPrenom($prenom !== '' ? $prenom : 'OAuth')
                ->setRole(User::ROLE_CLIENT)
                ->setStatut('actif')
                ->setDatecreation(new \DateTime())
                ->setMotdepasse(password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT));
            $em->persist($user);
        }

        if ($user->getDeletedAt() !== null) {
            $this->addFlash('error', 'Ce compte est desactive.');
            return $this->redirectToRoute('app_login');
        }

        $user
            ->setOauthProvider($provider)
            ->setOauthProviderId($providerId)
            ->setStatut('actif');

        $em->flush();

        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $tokenStorage->setToken($token);
        $session->set('_security_main', serialize($token));

        $role = strtoupper((string) $user->getRole());
        if (str_starts_with($role, 'ROLE_')) {
            $role = substr($role, 5);
        }

        $targetUrl = $this->resolvePostLoginUrl($user);
        if ($popupSession) {
            return new Response(
                '<!doctype html><html><head><meta charset="utf-8"><title>Connexion reussie</title></head><body style="font-family:Arial,sans-serif;padding:24px;">'
                . '<p>Connexion reussie. Cette fenetre va se fermer...</p>'
                . '<script>'
                . 'if (window.opener && !window.opener.closed) { window.opener.location.href = ' . json_encode($targetUrl) . '; window.close(); }'
                . '</script>'
                . '<p><a href="' . htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8') . '">Continuer</a></p>'
                . '</body></html>'
            );
        }

        return $this->redirect($targetUrl);
    }

    private function resolveHomeRouteByRole(User $user): string
    {
        $role = strtoupper((string) $user->getRole());
        if (str_starts_with($role, 'ROLE_')) {
            $role = substr($role, 5);
        }

        if ($role === 'ADMIN') {
            return 'front_admin';
        }
        if ($role === 'CLIENT') {
            return 'front_client';
        }
        if ($role === 'ARTISANT') {
            return 'front_artisan';
        }
        if ($role === 'LIVREUR') {
            return 'app_livreur_dashboard';
        }

        return 'home';
    }

    private function resolvePostLoginUrl(User $user): string
    {
        $role = strtoupper((string) $user->getRole());
        if (str_starts_with($role, 'ROLE_')) {
            $role = substr($role, 5);
        }

        if ($role === 'LIVREUR') {
            return $this->generateUrl('app_livreur_dashboard', ['id' => $user->getId()]);
        }

        return $this->generateUrl($this->resolveHomeRouteByRole($user));
    }

    /**
     * @return array{id:string,email:string,nom:string,prenom:string}|null
     */
    private function fetchOauthProfile(string $provider, string $code): ?array
    {
        $redirectUri = $this->generateUrl(
            'app_oauth_callback',
            ['provider' => $provider],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        if ($provider === 'google') {
            $clientId = $this->getEnvValue('GOOGLE_CLIENT_ID');
            $clientSecret = $this->getEnvValue('GOOGLE_CLIENT_SECRET');
            if ($clientId === '' || $clientSecret === '') {
                return null;
            }

            $tokenResponse = $this->httpRequest(
                'https://oauth2.googleapis.com/token',
                'POST',
                ['Content-Type: application/x-www-form-urlencoded'],
                http_build_query([
                    'grant_type' => 'authorization_code',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'redirect_uri' => $redirectUri,
                    'code' => $code,
                ])
            );

            $tokenData = json_decode((string) ($tokenResponse['body'] ?? ''), true);
            $accessToken = (string) ($tokenData['access_token'] ?? '');
            if ($accessToken === '') {
                return null;
            }

            $profileResponse = $this->httpRequest(
                'https://openidconnect.googleapis.com/v1/userinfo',
                'GET',
                ['Authorization: Bearer ' . $accessToken]
            );
            $profile = json_decode((string) ($profileResponse['body'] ?? ''), true);
            if (!is_array($profile)) {
                return null;
            }

            return [
                'id' => (string) ($profile['sub'] ?? ''),
                'email' => (string) ($profile['email'] ?? ''),
                'nom' => (string) ($profile['family_name'] ?? ''),
                'prenom' => (string) ($profile['given_name'] ?? ''),
            ];
        }

        $clientId = $this->getEnvValue('FACEBOOK_CLIENT_ID');
        $clientSecret = $this->getEnvValue('FACEBOOK_CLIENT_SECRET');
        if ($clientId === '' || $clientSecret === '') {
            return null;
        }

        $tokenUrl = 'https://graph.facebook.com/v20.0/oauth/access_token?' . http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);
        $tokenResponse = $this->httpRequest($tokenUrl, 'GET');
        $tokenData = json_decode((string) ($tokenResponse['body'] ?? ''), true);
        $accessToken = (string) ($tokenData['access_token'] ?? '');
        if ($accessToken === '') {
            return null;
        }

        $profileUrl = 'https://graph.facebook.com/me?' . http_build_query([
            'fields' => 'id,name,email,first_name,last_name',
            'access_token' => $accessToken,
        ]);
        $profileResponse = $this->httpRequest($profileUrl, 'GET');
        $profile = json_decode((string) ($profileResponse['body'] ?? ''), true);
        if (!is_array($profile)) {
            return null;
        }

        return [
            'id' => (string) ($profile['id'] ?? ''),
            'email' => (string) ($profile['email'] ?? ''),
            'nom' => (string) ($profile['last_name'] ?? ''),
            'prenom' => (string) ($profile['first_name'] ?? ''),
        ];
    }

    /**
     * @return array{status:int,body:string}
     */
    private function httpRequest(string $url, string $method = 'GET', array $headers = [], ?string $body = null): array
    {
        $headerString = implode("\r\n", $headers);
        $context = stream_context_create([
            'http' => [
                'method' => strtoupper($method),
                'header' => $headerString,
                'ignore_errors' => true,
                'content' => $body ?? '',
                'timeout' => 15,
            ],
        ]);

        $responseBody = @file_get_contents($url, false, $context);
        $status = 0;
        if (isset($http_response_header[0]) && preg_match('#HTTP/\S+\s+(\d{3})#', $http_response_header[0], $matches)) {
            $status = (int) $matches[1];
        }

        return [
            'status' => $status,
            'body' => $responseBody !== false ? $responseBody : '',
        ];
    }

    private function getEnvValue(string $key): string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if (!is_string($value)) {
            return '';
        }
        return trim($value);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(Request $request): Response
    {
        $this->addFlash('success', 'Vous êtes bien déconnecté');
        return $this->redirectToRoute('home');
    }
}
