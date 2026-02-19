<?php

namespace App\Repository;

use App\Entity\Produit;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    public function findBySearch(string $query, array $orderBy = [], string $etat = ''): array
    {
        $qb = $this->createQueryBuilder('p');
        if (!empty($query)) {
            $qb->andWhere('p.nomproduit LIKE :q OR p.typemateriau LIKE :q')
               ->setParameter('q', '%' . $query . '%');
        }
        if (!empty($etat)) {
            $qb->andWhere('p.etat = :etat')->setParameter('etat', $etat);
        }
        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy('p.' . $field, $direction);
        }
        return $qb->getQuery()->getResult();
    }

    /**
     * @return Produit[]
     */
    public function findByAddedBy(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.addedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('p.dateajout', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
