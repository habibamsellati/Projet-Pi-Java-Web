<?php

namespace App\Repository;

use App\Entity\Commande;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    /**
     * Recherche et filtre par état (back office).
     *
     * @param string|null $search Recherche dans nom/prénom client ou id commande
     * @param string|null $statut en_attente|valide|invalide
     * @return Commande[]
     */
    public function searchWithFilters(?string $search, ?string $statut): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.client', 'client')
            ->orderBy('c.datecommande', 'DESC');

        if ($statut !== null && $statut !== '') {
            $qb->andWhere('c.statut = :statut')
                ->setParameter('statut', $statut);
        }
        if ($search !== null && $search !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    'client.nom LIKE :search',
                    'client.prenom LIKE :search',
                    'c.numero LIKE :search'
                )
            )
                ->setParameter('search', '%' . $search . '%');
        }
        return $qb->getQuery()->getResult();
    }

    /**
     * @return Commande[]
     */
    public function findByClient(User $client): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.articles', 'a')->addSelect('a')
            ->where('c.client = :client')
            ->setParameter('client', $client)
            ->orderBy('c.datecommande', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Commande[] Returns an array of Commande objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    /**
     * Statistiques: nombre de commandes par statut.
     *
     * @return array<string, int>
     */
    public function getStatsByStatut(): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c.statut AS statut', 'COUNT(c.id) AS cnt')
            ->groupBy('c.statut');
        $result = $qb->getQuery()->getResult();
        $out = [];
        foreach ($result as $row) {
            $out[$row['statut']] = (int) $row['cnt'];
        }
        return $out;
    }

    /**
     * Chiffre d'affaires total (commandes validées).
     */
    public function getTotalRevenue(): float
    {
        $qb = $this->createQueryBuilder('c')
            ->select('SUM(c.total)')
            ->where('c.statut = :statut')
            ->setParameter('statut', 'valide');
        $result = $qb->getQuery()->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    //    public function findOneBySomeField($value): ?Commande
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
