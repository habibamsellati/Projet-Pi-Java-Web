<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\User;
use App\Form\CommandeValidationType;
use App\Repository\ArticleRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/panier')]
final class PanierController extends AbstractController
{
    private const SESSION_PANIER_KEY = 'panier_articles';

    #[Route(name: 'app_panier_index', methods: ['GET'])]
    public function index(SessionInterface $session, ArticleRepository $articleRepository): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getRole() !== User::ROLE_CLIENT) {
            $this->addFlash('error', 'Seuls les clients peuvent accéder au panier. Connectez-vous en tant que client.');
            return $this->redirectToRoute('app_login');
        }

        $panierIds = $session->get(self::SESSION_PANIER_KEY, []);
        $articles = [];
        $total = 0.0;
        foreach ($panierIds as $id) {
            $article = $articleRepository->find($id);
            if ($article) {
                $articles[] = $article;
                $prix = $article->getPrix();
                $total += $prix !== null && $prix !== '' ? (float) $prix : 0.0;
            }
        }

        return $this->render('panier/index.html.twig', [
            'articles' => $articles,
            'total' => $total,
        ]);
    }

    #[Route('/valider', name: 'app_panier_valider', methods: ['GET', 'POST'])]
    public function valider(SessionInterface $session, ArticleRepository $articleRepository, EntityManagerInterface $em, Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getRole() !== User::ROLE_CLIENT) {
            $this->addFlash('error', 'Seuls les clients peuvent valider une commande.');
            return $this->redirectToRoute('app_login');
        }

        $panierIds = $session->get(self::SESSION_PANIER_KEY, []);
        $articles = [];
        $total = 0.0;
        foreach ($panierIds as $id) {
            $article = $articleRepository->find($id);
            if ($article) {
                $articles[] = $article;
                $prix = $article->getPrix();
                $total += $prix !== null && $prix !== '' ? (float) $prix : 0.0;
            }
        }

        if (empty($articles)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_panier_index');
        }

        $form = $this->createForm(CommandeValidationType::class, [
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $commande = new Commande();
            $commande->setNumero($data['numero']);
            $commande->setDatecommande(new \DateTime());
            $commande->setStatut('en_attente');
            $commande->setTotal($total);
            $commande->setAdresselivraison($data['adresselivraison']);
            $commande->setModepaiement($data['modepaiement']);
            $commande->setClient($user);
            foreach ($articles as $article) {
                $commande->addArticle($article);
            }
            $em->persist($commande);
            $em->flush();

            $session->set(self::SESSION_PANIER_KEY, []);
            $this->addFlash('success', 'Votre commande a été enregistrée. Elle sera traitée par l\'équipe.');
            return $this->redirectToRoute('app_panier_historique');
        }

        return $this->render('panier/valider.html.twig', [
            'form' => $form,
            'articles' => $articles,
            'total' => $total,
        ]);
    }

    #[Route('/ajouter/{id}', name: 'app_panier_ajouter', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function ajouter(int $id, SessionInterface $session, ArticleRepository $articleRepository, Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getRole() !== User::ROLE_CLIENT) {
            $this->addFlash('error', 'Seuls les clients peuvent commander des articles. Connectez-vous en tant que client.');
            return $this->redirectToRoute('app_login');
        }

        $token = $request->request->get('_token');
        if (!$token || !$this->isCsrfTokenValid('panier_ajouter' . $id, $token)) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_article_index');
        }
        $article = $articleRepository->find($id);
        if (!$article) {
            $this->addFlash('error', 'Article introuvable.');
            return $this->redirectToRoute('app_article_index');
        }

        $panier = $session->get(self::SESSION_PANIER_KEY, []);
        $panier[] = $id;
        $session->set(self::SESSION_PANIER_KEY, $panier);

        $this->addFlash('success', '"' . $article->getTitre() . '" a été ajouté au panier.');

        $referer = $request->headers->get('Referer');
        if ($referer) {
            return $this->redirect($referer);
        }
        return $this->redirectToRoute('app_article_index');
    }

    #[Route('/retirer/{id}', name: 'app_panier_retirer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function retirer(int $id, SessionInterface $session, Request $request): Response
    {
        $token = $request->request->get('_token');
        if (!$token || !$this->isCsrfTokenValid('panier_retirer' . $id, $token)) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_panier_index');
        }
        $panier = $session->get(self::SESSION_PANIER_KEY, []);
        $panier = array_values(array_filter($panier, fn ($i) => (int) $i !== $id));
        $session->set(self::SESSION_PANIER_KEY, $panier);

        $this->addFlash('success', 'Article retiré du panier.');

        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/historique', name: 'app_panier_historique', methods: ['GET'])]
    public function historique(CommandeRepository $commandeRepository): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user || $user->getRole() !== User::ROLE_CLIENT) {
            $this->addFlash('error', 'Seuls les clients peuvent acceder a leur historique de commandes.');
            return $this->redirectToRoute('app_login');
        }

        $commandes = $commandeRepository->findByClient($user);

        return $this->render('panier/historique.html.twig', [
            'commandes' => $commandes,
        ]);
    }
}
