<?php

namespace App\Repository;

use App\Entity\PromptTemplate;
use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PromptTemplate>
 */
class PromptTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PromptTemplate::class);
    }

    /**
     * Find all templates ordered by title
     * @return PromptTemplate[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find templates by target model
     * @return PromptTemplate[]
     */
    public function findByTargetModel(string $model): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.targetModel = :model')
            ->setParameter('model', $model)
            ->orderBy('t.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find templates by tag
     * @return PromptTemplate[]
     */
    public function findByTag(Tag $tag): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.tags', 'tag')
            ->where('tag = :tag')
            ->setParameter('tag', $tag)
            ->orderBy('t.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search templates by query
     * @return PromptTemplate[]
     */
    public function search(string $query): array
    {
        $searchTerm = '%' . $query . '%';

        return $this->createQueryBuilder('t')
            ->where('LOWER(t.title) LIKE LOWER(:query)')
            ->orWhere('LOWER(t.body) LIKE LOWER(:query)')
            ->orWhere('LOWER(t.description) LIKE LOWER(:query)')
            ->setParameter('query', $searchTerm)
            ->orderBy('t.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recent templates
     * @return PromptTemplate[]
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->addSelect('COALESCE(t.updatedAt, t.createdAt) AS HIDDEN lastModified')
            ->orderBy('lastModified', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
