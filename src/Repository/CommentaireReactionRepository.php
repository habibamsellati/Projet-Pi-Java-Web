<?php

namespace App\Repository;

use App\Entity\CommentaireReaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommentaireReaction>
 */
class CommentaireReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentaireReaction::class);
    }

    /**
     * @return array<int, array{likes: int, dislikes: int}>
     */
    public function getStatsForArticle(int $articleId): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.commentaire) AS commentId')
            ->addSelect('SUM(CASE WHEN r.type = :likeType THEN 1 ELSE 0 END) AS likes')
            ->addSelect('SUM(CASE WHEN r.type = :dislikeType THEN 1 ELSE 0 END) AS dislikes')
            ->innerJoin('r.commentaire', 'c')
            ->innerJoin('c.article', 'a')
            ->andWhere('a.id = :articleId')
            ->setParameter('articleId', $articleId)
            ->setParameter('likeType', CommentaireReaction::TYPE_LIKE)
            ->setParameter('dislikeType', CommentaireReaction::TYPE_DISLIKE)
            ->groupBy('r.commentaire')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($rows as $row) {
            $commentId = (int) $row['commentId'];
            $stats[$commentId] = [
                'likes' => (int) $row['likes'],
                'dislikes' => (int) $row['dislikes'],
            ];
        }

        return $stats;
    }

    /**
     * @return array<int, int> Map commentId => reactionType
     */
    public function getUserReactionsForArticle(int $articleId, int $userId): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.commentaire) AS commentId', 'r.type AS type')
            ->innerJoin('r.commentaire', 'c')
            ->innerJoin('c.article', 'a')
            ->andWhere('a.id = :articleId')
            ->andWhere('IDENTITY(r.user) = :userId')
            ->setParameter('articleId', $articleId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();

        $reactions = [];
        foreach ($rows as $row) {
            $reactions[(int) $row['commentId']] = (int) $row['type'];
        }

        return $reactions;
    }
}

