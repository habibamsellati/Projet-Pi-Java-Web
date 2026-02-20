<?php

namespace App\Controller;

use App\Entity\Livraison;
use App\Repository\LivraisonRepository;
use App\Repository\SuiviLivraisonRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LivreurController extends AbstractController
{
    #[Route('/espace-livreur/{id}', name: 'app_livreur_dashboard', requirements: ['id' => '\d+'])]
    public function index(int $id, LivraisonRepository $repository, UserRepository $userRepo): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }
        if (strtoupper((string) $currentUser->getRole()) !== 'ADMIN' && (int) $currentUser->getId() !== $id) {
            throw $this->createAccessDeniedException('Vous ne pouvez accéder qu’à votre propre espace livreur.');
        }
        $user = $userRepo->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Livreur non trouvé');
        }

        // Only show livraisons that are 'en_attente' or 'en_cours' (exclude livree/livré)
        $livraisons = $repository->createQueryBuilder('l')
            ->where('l.livreur = :livreur')
            ->andWhere('l.statutlivraison NOT IN (:status)')
            ->setParameter('livreur', $user)
            ->setParameter('status', ['livree', 'livré'])
            ->orderBy('l.datelivraison', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('livreur/index.html.twig', [
            'livraisons' => $livraisons,
            'livreur' => $user,
        ]);
    }

    #[Route('/espace-livreur/update-status/{id}', name: 'app_livreur_update_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateStatus(
        Livraison $livraison,
        Request $request,
        EntityManagerInterface $em,
        SuiviLivraisonRepository $suiviRepo
    ): Response {
        if (!$this->getUser()) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }
        if (!$this->isCsrfTokenValid('livreur_update', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('home');
        }
        $livreur = $livraison->getLivreur();
        if (!$livreur) {
            $this->addFlash('error', 'Livraison sans livreur assigné.');
            return $this->redirectToRoute('home');
        }
        $currentUser = $this->getUser();
        if (strtoupper((string) $currentUser->getRole()) !== 'ADMIN' && (int) $currentUser->getId() !== $livreur->getId()) {
            throw $this->createAccessDeniedException('Seul le livreur assigné peut modifier cette livraison.');
        }

        $currentStatus = trim(strtolower($livraison->getStatutlivraison()));

        if ($currentStatus === 'en_attente' || $currentStatus === 'en attente') {
            $newStatus = 'en_cours';
            $message = 'Livraison démarrée ! 🚀';
        } else {
            $newStatus = 'livre';
            $message = 'Mission accomplie ! La livraison a été enregistrée dans votre historique. ✨';
        }

        $livraison->setStatutlivraison($newStatus);

        $suivi = $suiviRepo->findOneBy(['livraison' => $livraison]);
        if ($suivi) {
            $suivi->setEtat($newStatus);
            $suivi->setDatesuivi(new \DateTime());
        }

        $em->flush();
        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_livreur_dashboard', ['id' => $livreur->getId()]);
    }

    #[Route('/espace-livreur/{id}/historique', name: 'app_livreur_historique', requirements: ['id' => '\d+'])]
    public function historique(int $id, LivraisonRepository $repository, UserRepository $userRepo): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }
        if (strtoupper((string) $currentUser->getRole()) !== 'ADMIN' && (int) $currentUser->getId() !== $id) {
            throw $this->createAccessDeniedException('Vous ne pouvez accéder qu’à votre propre historique.');
        }
        $user = $userRepo->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Livreur non trouvé');
        }

        $historique = $repository->createQueryBuilder('l')
            ->where('l.livreur = :livreur')
            ->andWhere('l.statutlivraison IN (:status)')
            ->setParameter('livreur', $user)
            ->setParameter('status', ['livree', 'livré'])
            ->orderBy('l.datelivraison', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('livreur/historique.html.twig', [
            'historique' => $historique,
            'livreur' => $user,
        ]);
    }
}
