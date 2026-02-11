<?php

namespace App\Repository;

use App\Entity\Livraison;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
/**
 * @extends ServiceEntityRepository<Livraison>
 */
class LivraisonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Livraison::class);
    }

    //    /**
    //     * @return Livraison[] Returns an array of Livraison objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('l.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Livraison
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function countByAdresse(): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.addresslivraison AS adresse, COUNT(l.id) AS total')
            ->groupBy('l.addresslivraison')
            ->getQuery()
            ->getResult();
    }

    public function findAllOrderByDateAsc()
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.datelivraison', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne toutes les livraisons pour un client donné (user)
     *
     * @return Livraison[]
     */
    public function findByClient(User $client): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.commande', 'c')
            ->andWhere('c.client = :client')
            ->setParameter('client', $client)
            ->orderBy('l.datelivraison', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
