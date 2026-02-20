<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 *
 * @method Evenement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Evenement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Evenement[]    findAll()
 * @method Evenement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /**
     * @return Evenement[]
     */
    public function findBySearchAndSort(?string $search, ?string $sort, ?string $order): array
    {
        $qb = $this->createQueryBuilder('e');

        if ($search) {
            $qb->andWhere('e.nom LIKE :search OR e.description LIKE :search OR e.lieu LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($sort) {
            $qb->orderBy('e.' . $sort, $order === 'DESC' ? 'DESC' : 'ASC');
        } else {
            $qb->orderBy('e.id', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    public function getStats(): array
    {
        $total = $this->count([]);
        $capacity = $this->createQueryBuilder('e')
            ->select('SUM(e.capacite)')
            ->getQuery()
            ->getSingleScalarResult();
        return [
            'total' => $total,
            'capacity' => $capacity ?? 0
        ];
    }

    public function getCapacityData(): array
    {
        return $this->createQueryBuilder('e')
            ->select('e.nom as label, e.capacite as value')
            ->orderBy('e.capacite', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }
}
