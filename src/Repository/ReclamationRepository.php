<?php

namespace App\Repository;

use App\Entity\Reclamation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reclamation>
 */
class ReclamationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reclamation::class);
    }

    /**
     * Recherche et filtrage des réclamations
     */
    public function searchWithFilters(?string $search = null, ?string $statut = null, ?string $tri = 'date_desc', ?int $userId = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')
            ->addSelect('u');

        if ($userId !== null) {
            $qb->andWhere('r.user = :userId')
                ->setParameter('userId', $userId);
        }

        if ($search !== null && $search !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('r.titre', ':search'),
                    $qb->expr()->like('r.descripition', ':search')
                )
            )
            ->setParameter('search', '%' . $search . '%');
        }

        if ($statut !== null && $statut !== '') {
            $qb->andWhere('r.statut = :statut')
                ->setParameter('statut', $statut);
        }

        switch ($tri) {
            case 'date_asc':
                $qb->orderBy('r.datecreation', 'ASC');
                break;
            case 'titre_asc':
                $qb->orderBy('r.titre', 'ASC');
                break;
            case 'titre_desc':
                $qb->orderBy('r.titre', 'DESC');
                break;
            case 'date_desc':
            default:
                $qb->orderBy('r.datecreation', 'DESC');
                break;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Statistiques: nombre de réclamations par statut.
     *
     * @return array<string, int>
     */
    public function getStatsByStatut(): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.statut AS statut', 'COUNT(r.id) AS cnt')
            ->groupBy('r.statut');
        $result = $qb->getQuery()->getResult();
        $out = [];
        foreach ($result as $row) {
            $out[$row['statut']] = (int) $row['cnt'];
        }
        return $out;
    }

    /**
     * Find reclamations that are pending for more than 48 hours without response
     *
     * @return Reclamation[]
     */
    public function findPendingOver48Hours(): array
    {
        $fortyEightHoursAgo = new \DateTime('-48 hours');

        return $this->createQueryBuilder('r')
            ->leftJoin('r.reponseReclamations', 'rr')
            ->leftJoin('r.user', 'u')
            ->addSelect('u')
            ->where('r.statut = :statut')
            ->andWhere('r.datecreation <= :date')
            ->andWhere('rr.id IS NULL') // No responses yet
            ->setParameter('statut', 'en_attente')
            ->setParameter('date', $fortyEightHoursAgo)
            ->orderBy('r.datecreation', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
