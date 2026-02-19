<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 *
 * @method Reservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reservation[]    findAll()
 * @method Reservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * @return Reservation[]
     */
    public function findBySearchAndSort(?string $search, ?string $sort, ?string $order): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.evenement', 'e')
            ->addSelect('e');

        if ($search) {
            $qb->andWhere('e.nom LIKE :search OR r.statut LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($sort) {
            if ($sort === 'evenement') {
                $qb->orderBy('e.nom', $order === 'DESC' ? 'DESC' : 'ASC');
            } else {
                $qb->orderBy('r.' . $sort, $order === 'DESC' ? 'DESC' : 'ASC');
            }
        } else {
            $qb->orderBy('r.id', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    public function getStats(): array
    {
        $total = $this->count([]);
        $pending = $this->count(['statut' => 'en_attente']);
        $confirmed = $this->count(['statut' => 'confirme']);
        $canceled = $this->count(['statut' => 'annule']);
        return [
            'total' => $total,
            'pending' => $pending,
            'confirmed' => $confirmed,
            'canceled' => $canceled
        ];
    }
}
