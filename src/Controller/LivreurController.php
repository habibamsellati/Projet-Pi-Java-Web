<?php

namespace App\Controller;

use App\Entity\Livraison;
use App\Repository\LivraisonRepository;
use App\Repository\UserRepository;
use App\Repository\SuiviLivraisonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LivreurController extends AbstractController
{
    #[Route('/espace-livreur/{id}', name: 'app_livreur_dashboard')]
    public function index(int $id, LivraisonRepository $repository, UserRepository $userRepo): Response
    {
        $user = $userRepo->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Livreur non trouvé');
        }

        // --- CORRECTION : Filtrage pour ne PAS afficher les livraisons terminées ---
        // On récupère les livraisons qui sont 'en_attente' ou 'en_cours'
        $livraisons = $repository->createQueryBuilder('l')
            ->where('l.livreur = :livreur')
            ->andWhere('l.statutlivraison NOT IN (:status)')
            ->setParameter('livreur', $user)
            ->setParameter('status', ['livree', 'livré']) // On exclut les deux variantes
            ->getQuery()
            ->getResult();

        return $this->render('livreur/index.html.twig', [
            'livraisons' => $livraisons,
            'livreur' => $user
        ]);
    }

    #[Route('/espace-livreur/update-status/{id}', name: 'app_livreur_update_status', methods: ['POST'])]
    public function updateStatus(
        Livraison $livraison, 
        EntityManagerInterface $em, 
        SuiviLivraisonRepository $suiviRepo
    ): Response {
        $livreur = $livraison->getLivreur();
        
        // Nettoyage du statut actuel pour la comparaison
        $currentStatus = trim(strtolower($livraison->getStatutlivraison()));

        // Logique : En attente -> En cours -> Livrée
        if ($currentStatus === 'en_attente' || $currentStatus === 'en attente') {
            $newStatus = 'en_cours';
            $message = 'Livraison démarrée ! 🚀';
        } else {
            $newStatus = 'livree';
            $message = 'Mission accomplie ! La livraison a été enregistrée dans votre historique. ✨';
        }

        // Mise à jour de la Livraison
        $livraison->setStatutlivraison($newStatus);

        // Synchronisation avec SuiviLivraison
        $suivi = $suiviRepo->findOneBy(['livraison' => $livraison]);
        if ($suivi) {
            if (method_exists($suivi, 'setEtat')) {
                $suivi->setEtat($newStatus);
            } elseif (method_exists($suivi, 'setStatut')) {
                $suivi->setStatut($newStatus);
            }
            $suivi->setDateSuivi(new \DateTime());
        }

        $em->flush();
        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_livreur_dashboard', ['id' => $livreur->getId()]);
    }

    #[Route('/espace-livreur/{id}/historique', name: 'app_livreur_historique')]
    public function historique(int $id, LivraisonRepository $repository, UserRepository $userRepo): Response
    {
        $user = $userRepo->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Livreur non trouvé');
        }

        // Ici on récupère UNIQUEMENT ce qui est livré
        $historique = $repository->findBy(
            ['livreur' => $user, 'statutlivraison' => ['livree', 'livré']],
            ['datelivraison' => 'DESC']
        );

        return $this->render('livreur/historique.html.twig', [
            'historique' => $historique,
            'livreur' => $user
        ]);
    }
}