<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return User[] Returns an array of User objects
     */
    public function searchAndSort(?string $query, ?string $sort, ?string $dir, bool $includeDeleted = false): array
    {
        $qb = $this->createQueryBuilder('u');

        if (!$includeDeleted) {
            $qb->andWhere('u.deletedAt IS NULL');
        }

        $query = $query !== null ? trim($query) : null;
        if ($query !== null && $query !== '') {
            $qb->andWhere(
                'LOWER(u.nom) LIKE :q OR LOWER(u.prenom) LIKE :q OR LOWER(u.email) LIKE :q OR LOWER(u.role) LIKE :q OR LOWER(u.statut) LIKE :q'
            )
            ->setParameter('q', '%' . strtolower($query) . '%');
        }

        $allowedSorts = [
            'prenom' => 'u.prenom',
            'nom' => 'u.nom',
            'email' => 'u.email',
            'role' => 'u.role',
            'statut' => 'u.statut',
            'datecreation' => 'u.datecreation',
        ];

        $sortField = $allowedSorts[$sort] ?? 'u.datecreation';
        $direction = strtoupper((string) $dir) === 'ASC' ? 'ASC' : 'DESC';

        $qb->orderBy($sortField, $direction)
            ->addOrderBy('u.id', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Nombre d'utilisateurs par rôle.
     *
     * @return array<string, int>
     */
    public function countByRole(): array
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u.role AS role', 'COUNT(u.id) AS cnt')
            ->groupBy('u.role');
        $result = $qb->getQuery()->getResult();
        $out = [];
        foreach ($result as $row) {
            $out[$row['role']] = (int) $row['cnt'];
        }
        return $out;
    }

    /**
     * @return User[] Returns an array of deleted User objects
     */
    public function findDeleted(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.deletedAt IS NOT NULL')
            ->orderBy('u.deletedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByValidResetToken(string $plainToken): ?User
    {
        $tokenHash = hash('sha256', $plainToken);

        return $this->createQueryBuilder('u')
            ->andWhere('u.resetTokenHash = :tokenHash')
            ->andWhere('u.resetTokenExpiresAt IS NOT NULL')
            ->andWhere('u.resetTokenExpiresAt > :now')
            ->setParameter('tokenHash', $tokenHash)
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
