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
    // On enlève le {id} de l'URL pour plus de sécurité, on utilise l'utilisateur connecté
    #[Route('/espace-livreur', name: 'app_livreur_dashboard')]
    public function index(LivraisonRepository $repository): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer les missions en cours (non livrées)
        $livraisons = $repository->createQueryBuilder('l')
            ->where('l.livreur = :livreur')
            ->andWhere('l.statutlivraison NOT IN (:status)')
            ->setParameter('livreur', $user)
            ->setParameter('status', ['livre'])
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
        $user = $this->getUser();
        if (!$user) throw $this->createAccessDeniedException();

        if (!$this->isCsrfTokenValid('livreur_update', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton invalide.');
            return $this->redirectToRoute('app_livreur_dashboard');
        }

        $currentStatus = trim(strtolower($livraison->getStatutlivraison()));

        if (in_array($currentStatus, ['en_attente', 'en attente'])) {
            $newStatus = 'en_cours';
            $message = 'Livraison démarrée ! 🚀';
        } else {
            $newStatus = 'livre';
            $message = 'Mission accomplie ! ✨';
        }

        $livraison->setStatutlivraison($newStatus);

        $suivi = $suiviRepo->findOneBy(['livraison' => $livraison]);
        if ($suivi) {
            $suivi->setEtat($newStatus);
            $suivi->setDatesuivi(new \DateTime());
        }

        $em->flush();
        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_livreur_dashboard');
    }

    #[Route('/espace-livreur/historique', name: 'app_livreur_historique')]
    public function historique(LivraisonRepository $repository): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $historique = $repository->createQueryBuilder('l')
            ->where('l.livreur = :livreur')
            ->andWhere('l.statutlivraison IN (:status)')
            ->setParameter('livreur', $user)
            ->setParameter('status', ['livre'])
            ->orderBy('l.datelivraison', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('livreur/historique.html.twig', [
            'historique' => $historique,
            'livreur' => $user,
        ]);
    }
}