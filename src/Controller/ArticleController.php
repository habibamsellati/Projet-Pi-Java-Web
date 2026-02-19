<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Commentaire;
use App\Entity\User;
use App\Form\Article1Type;
use App\Form\CommentaireType;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/article')]
final class ArticleController extends AbstractController
{
    private const ARTICLE_CATEGORIES = ['Artisanat', 'Décoration', 'Textile', 'Céramique', 'Autres'];

    #[Route(name: 'app_article_index', methods: ['GET'])]
    public function index(Request $request, ArticleRepository $articleRepository, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        $isClient = $user && $user->getRole() === User::ROLE_CLIENT;
        $isArtisan = $user && $user->getRole() === User::ROLE_ARTISAN;
        $isAdmin = $user && $user->getRole() === User::ROLE_ADMIN;

        $search = $request->query->get('recherche', '');
        $categorie = $request->query->get('categorie');
        $artisanId = $request->query->get('auteur');
        $tri = $request->query->get('tri', 'date_desc');

        $articles = $articleRepository->searchWithFilters(
            $search !== '' ? $search : null,
            $categorie !== null && $categorie !== '' ? (string) $categorie : null,
            $artisanId !== null && $artisanId !== '' ? (int) $artisanId : null,
            in_array($tri, ['date_desc', 'date_asc', 'titre_asc', 'titre_desc', 'prix_asc', 'prix_desc'], true) ? $tri : 'date_desc'
        );
        $categories = self::ARTICLE_CATEGORIES;
        $artisans = $articleRepository->getArtisansWithArticles();

        return $this->render('article/index.html.twig', [
            'articles' => $articles,
            'categories' => $categories,
            'artisans' => $artisans,
            'isClient' => $isClient,
            'isArtisan' => $isArtisan,
            'isAdmin' => $isAdmin,
            'user' => $user,
            'filters' => [
                'recherche' => $search,
                'categorie' => $categorie,
                'auteur' => $artisanId,
                'tri' => $tri,
            ],
        ]);
    }

    #[Route('/new', name: 'app_article_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        if ($user && $user->getRole() === User::ROLE_CLIENT) {
            $this->addFlash('error', 'Les clients ne peuvent pas créer d\'articles.');
            return $this->redirectToRoute('app_article_index');
        }

        $article = new Article();
        $article->setDate(new \DateTime());

        $form = $this->createForm(Article1Type::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $artisan = null;
            if ($user && in_array($user->getRole(), [User::ROLE_ARTISAN, User::ROLE_ADMIN], true)) {
                $artisan = $user;
            } elseif ($this->getUser()) {
                $artisan = $this->getUser();
            } else {
                $artisan = $userRepository->createQueryBuilder('u')
                    ->where('u.role = :role')
                    ->setParameter('role', User::ROLE_ARTISAN)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
                if (!$artisan) {
                    $artisan = $userRepository->findOneBy([], ['id' => 'ASC']);
                }
            }

            if ($artisan) {
                $article->setArtisan($artisan);
                $article->setUser($artisan);
            }

            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/image',
                        $newFilename
                    );
                    $article->setImage($newFilename);
                } catch (\Exception $e) {
                }
            }

            $entityManager->persist($article);
            $entityManager->flush();

            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('article/new.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_article_show', methods: ['GET', 'POST'])]
    public function show(Article $article, Request $request, EntityManagerInterface $entityManager): Response
    {
        $commentaire = new Commentaire();
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        $user = $this->getUser();
        $isClient = $user && $user->getRole() === User::ROLE_CLIENT;
        $isArtisan = $user && $user->getRole() === User::ROLE_ARTISAN;
        $isAdmin = $user && $user->getRole() === User::ROLE_ADMIN;

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$isClient) {
                $this->addFlash('error', 'Vous devez être connecté en tant que client pour commenter.');
                return $this->redirectToRoute('app_login');
            }
            $commentaire->setArticle($article);
            $commentaire->setUser($user);
            $commentaire->setDatepub(new \DateTime());
            $entityManager->persist($commentaire);
            $entityManager->flush();
            $this->addFlash('success', 'Votre commentaire a été ajouté avec succès!');
            return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
        }

        return $this->render('article/show.html.twig', [
            'article' => $article,
            'form' => $form,
            'isClient' => $isClient,
            'isArtisan' => $isArtisan,
            'isAdmin' => $isAdmin,
            'user' => $user,
        ]);
    }

    #[Route('/{id}/like', name: 'app_article_like', methods: ['POST'])]
    public function like(Article $article): Response
    {
        $user = $this->getUser();
        if (!$user || $user->getRole() !== User::ROLE_CLIENT) {
            $this->addFlash('error', 'Vous devez être connecté en tant que client pour aimer un article.');
            return $this->redirectToRoute('app_login');
        }
        $this->addFlash('success', 'Vous avez aimé cet article!');
        return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
    }

    #[Route('/{id}/favorite', name: 'app_article_favorite', methods: ['POST'])]
    public function favorite(Article $article): Response
    {
        $user = $this->getUser();
        if (!$user || $user->getRole() !== User::ROLE_CLIENT) {
            $this->addFlash('error', 'Vous devez être connecté en tant que client pour ajouter un article aux favoris.');
            return $this->redirectToRoute('app_login');
        }
        $this->addFlash('success', 'Article ajouté aux favoris!');
        return $this->redirectToRoute('app_article_show', ['id' => $article->getId()]);
    }

    #[Route('/{id}/edit', name: 'app_article_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour modifier un article.');
            return $this->redirectToRoute('app_login');
        }
        if ($user->getRole() === User::ROLE_CLIENT) {
            $this->addFlash('error', 'Les clients ne peuvent pas modifier les articles.');
            return $this->redirectToRoute('app_article_index');
        }
        if ($user->getRole() === User::ROLE_ARTISAN && $article->getArtisan()?->getId() !== $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres articles.');
            return $this->redirectToRoute('app_article_index');
        }

        $oldImage = $article->getImage();
        $form = $this->createForm(Article1Type::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setDate(new \DateTime());
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                if ($oldImage && file_exists($this->getParameter('kernel.project_dir') . '/public/image/' . $oldImage)) {
                    unlink($this->getParameter('kernel.project_dir') . '/public/image/' . $oldImage);
                }
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/image',
                        $newFilename
                    );
                    $article->setImage($newFilename);
                } catch (\Exception $e) {
                }
            } else {
                $article->setImage($oldImage);
            }
            $entityManager->flush();
            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/commentaire/{commentaire}/modifier', name: 'app_commentaire_modifier', requirements: ['commentaire' => '\d+'], methods: ['GET', 'POST'])]
    public function modifierCommentaire(Commentaire $commentaire, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || $user->getRole() !== User::ROLE_CLIENT || $commentaire->getUser()?->getId() !== $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres commentaires.');
            return $this->redirectToRoute('app_article_show', ['id' => $commentaire->getArticle()->getId()]);
        }
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Commentaire modifié.');
            return $this->redirectToRoute('app_article_show', ['id' => $commentaire->getArticle()->getId()]);
        }
        return $this->render('article/commentaire_modifier.html.twig', [
            'form' => $form,
            'commentaire' => $commentaire,
            'article' => $commentaire->getArticle(),
        ]);
    }

    #[Route('/commentaire/{commentaire}/supprimer', name: 'app_commentaire_supprimer', requirements: ['commentaire' => '\d+'], methods: ['POST'])]
    public function supprimerCommentaire(Commentaire $commentaire, Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user || $user->getRole() !== User::ROLE_CLIENT || $commentaire->getUser()?->getId() !== $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres commentaires.');
            return $this->redirectToRoute('app_article_show', ['id' => $commentaire->getArticle()->getId()]);
        }
        $articleId = $commentaire->getArticle()->getId();
        $token = $request->request->get('_token');
        if ($token && $this->isCsrfTokenValid('commentaire_supprimer' . $commentaire->getId(), $token)) {
            $entityManager->remove($commentaire);
            $entityManager->flush();
            $this->addFlash('success', 'Commentaire supprimé.');
        }
        return $this->redirectToRoute('app_article_show', ['id' => $articleId], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/supprimer', name: 'app_article_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour supprimer un article.');
            return $this->redirectToRoute('app_login');
        }
        if ($user->getRole() === User::ROLE_CLIENT) {
            $this->addFlash('error', 'Les clients ne peuvent pas supprimer les articles.');
            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }
        if ($user->getRole() === User::ROLE_ARTISAN && $article->getArtisan()?->getId() !== $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres articles.');
            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }
        $token = $request->request->get('_token');
        if ($token && $this->isCsrfTokenValid('delete' . $article->getId(), $token)) {
            $entityManager->remove($article);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
    }
}
