<?php

namespace App\Repository;

use App\Entity\Proposition;
use App\Entity\User;
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
            ))->setParameter('search', '%' . $produitName . '%');
        }
        if ($etat) {
            $qb->andWhere('prod.etat = :etat')->setParameter('etat', $etat);
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

    /**
     * Propositions for produits added by the given owner (client).
     *
     * @return Proposition[]
     */
    public function findByProduitOwner(User $owner): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.produit', 'prod')
            ->andWhere('prod.addedBy = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('p.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Propositions visible to a client: on products they added OR submitted by them.
     *
     * @return Proposition[]
     */
    public function findForClient(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.produit', 'prod')
            ->addSelect('prod')
            ->andWhere('prod.addedBy = :user OR p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
