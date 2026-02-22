<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Recherche et filtre des articles (pour client et artisan).
     *
     * @param string|null $search Recherche dans titre/contenu
     * @param string|null $categorie Filtre par catégorie (nom)
     * @param int|null $artisanId Filtre par auteur (artisan)
     * @param string $sort date_desc|date_asc|titre_asc|titre_desc|prix_asc|prix_desc
     * @return QueryBuilder
     */
    public function createSearchQueryBuilder(?string $search, ?string $categorie, ?int $artisanId, string $sort = 'date_desc'): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.artisan', 'art');

        if ($search !== null && $search !== '') {
            $qb->andWhere('a.titre LIKE :search OR a.contenu LIKE :search OR art.nom LIKE :search OR art.prenom LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        if ($categorie !== null && $categorie !== '') {
            $qb->andWhere('a.categorie = :categorie')
                ->setParameter('categorie', $categorie);
        }
        if ($artisanId !== null) {
            $qb->andWhere('art.id = :artisanId')
                ->setParameter('artisanId', $artisanId);
        }

        switch ($sort) {
            case 'date_asc':
                $qb->orderBy('a.date', 'ASC');
                break;
            case 'titre_asc':
                $qb->orderBy('a.titre', 'ASC');
                break;
            case 'titre_desc':
                $qb->orderBy('a.titre', 'DESC');
                break;
            case 'prix_asc':
                $qb->orderBy('a.prix', 'ASC');
                break;
            case 'prix_desc':
                $qb->orderBy('a.prix', 'DESC');
                break;
            default:
                $qb->orderBy('a.date', 'DESC');
        }

        return $qb;
    }

    /**
     * @return Article[]
     */
    public function searchWithFilters(?string $search, ?string $categorie, ?int $artisanId, string $sort = 'date_desc'): array
    {
        return $this->createSearchQueryBuilder($search, $categorie, $artisanId, $sort)
            ->getQuery()
            ->getResult();
    }

    /**
     * Liste des artisans ayant au moins un article (pour filtre auteur).
     *
     * @return array<int, array{id: int, nom: string}>
     */
    public function getArtisansWithArticles(): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('art.id', 'art.nom', 'art.prenom')
            ->innerJoin('a.artisan', 'art')
            ->orderBy('art.nom', 'ASC')
            ->groupBy('art.id')
            ->addGroupBy('art.nom')
            ->addGroupBy('art.prenom');
        $results = $qb->getQuery()->getResult();
        $out = [];
        foreach ($results as $row) {
            $out[] = [
                'id' => $row['id'],
                'nom' => trim(($row['prenom'] ?? '') . ' ' . ($row['nom'] ?? '')),
            ];
        }
        return $out;
    }

    /**
     * Statistiques: nombre d'articles par catégorie.
     *
     * @return array<string, int>
     */
    public function getStatsByCategorie(): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.categorie AS cat', 'COUNT(a.id) AS cnt')
            ->where('a.categorie IS NOT NULL')
            ->andWhere("a.categorie != ''")
            ->groupBy('a.categorie');
        $result = $qb->getQuery()->getResult();
        $out = [];
        foreach ($result as $row) {
            $out[$row['cat']] = (int) $row['cnt'];
        }
        return $out;
    }

    /**
     * Top artisans par nombre d'articles.
     *
     * @return array<int, array{id: int, nom: string, count: int}>
     */
    public function getTopArtisans(int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('art.id', 'art.nom', 'art.prenom', 'COUNT(a.id) AS cnt')
            ->innerJoin('a.artisan', 'art')
            ->groupBy('art.id')
            ->addGroupBy('art.nom')
            ->addGroupBy('art.prenom')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults($limit);
        $rows = $qb->getQuery()->getResult();
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => (int) $r['id'],
                'nom' => trim(($r['prenom'] ?? '') . ' ' . ($r['nom'] ?? '')),
                'count' => (int) $r['cnt'],
            ];
        }
        return $out;
    }

    /**
     * Retourne des articles similaires au contexte courant.
     * Priorite: meme categorie, puis fallback sur les plus recents.
     *
     * @return Article[]
     */
    public function getSimilarArticles(Article $article, int $limit = 3): array
    {
        if ($limit <= 0 || !$article->getId()) {
            return [];
        }

        $similar = [];
        $excludeId = (int) $article->getId();
        $categorie = $article->getCategorie();

        if ($categorie !== null && $categorie !== '') {
            $similar = $this->createQueryBuilder('a')
                ->andWhere('a.id != :excludeId')
                ->andWhere('a.categorie = :categorie')
                ->setParameter('excludeId', $excludeId)
                ->setParameter('categorie', $categorie)
                ->orderBy('a.date', 'DESC')
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult();
        }

        $missing = $limit - count($similar);
        if ($missing > 0) {
            $existingIds = array_map(static fn (Article $a): int => (int) $a->getId(), $similar);
            $existingIds[] = $excludeId;

            $fallback = $this->createQueryBuilder('a')
                ->andWhere('a.id NOT IN (:excludeIds)')
                ->setParameter('excludeIds', $existingIds)
                ->orderBy('a.date', 'DESC')
                ->setMaxResults($missing)
                ->getQuery()
                ->getResult();

            $similar = array_merge($similar, $fallback);
        }

        return $similar;
    }
}
