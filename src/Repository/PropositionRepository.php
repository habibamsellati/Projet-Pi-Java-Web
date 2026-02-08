<?php

namespace App\Repository;

use App\Entity\Proposition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Proposition>
 */
class PropositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Proposition::class);
    }

//    /**
//     * @return Proposition[] Returns an array of Proposition objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }


    /**
     * Recherche les propositions par nom de produit, type de matériau et/ou état du produit
     * @return Proposition[] Returns an array of Proposition objects
     */
    public function findByProduitAndEtat(string $produitName = '', string $etat = '', array $orderBy = []): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.produit', 'prod')
            ->addSelect('prod');
        
        if ($produitName) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('prod.nomproduit', ':search'),
                $qb->expr()->like('prod.typemateriau', ':search'),
                $qb->expr()->like('p.titre', ':search'),
                $qb->expr()->like('p.description', ':search')
            ))
            ->setParameter('search', '%' . $produitName . '%');
        }
        
        if ($etat) {
            $qb->andWhere('prod.etat = :etat')
               ->setParameter('etat', $etat);
        }

        if (empty($orderBy)) {
            $qb->orderBy('p.date', 'DESC');
        } else {
            foreach ($orderBy as $field => $direction) {
                $qb->addOrderBy('p.' . $field, $direction);
            }
        }
        
        return $qb->getQuery()->getResult();
    }

//    public function findOneBySomeField($value): ?Proposition
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
