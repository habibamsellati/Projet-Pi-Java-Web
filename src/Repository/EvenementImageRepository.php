<?php

namespace App\Repository;

use App\Entity\EvenementImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EvenementImage>
 *
 * @method EvenementImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method EvenementImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method EvenementImage[]    findAll()
 * @method EvenementImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvenementImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EvenementImage::class);
    }
}
