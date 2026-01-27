<?php

namespace App\Repository;

use App\Entity\Context;
use App\Entity\Project;
use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Context>
 */
class ContextRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Context::class);
    }

    /**
     * Find all global contexts
     * @return Context[]
     */
    public function findGlobal(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.scope = :scope')
            ->setParameter('scope', Context::SCOPE_GLOBAL)
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find contexts by project
     * @return Context[]
     */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.project = :project')
            ->setParameter('project', $project)
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find contexts by tag
     * @return Context[]
     */
    public function findByTag(Tag $tag): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.tags', 't')
            ->where('t = :tag')
            ->setParameter('tag', $tag)
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search contexts by query
     * @return Context[]
     */
    public function search(string $query): array
    {
        $searchTerm = '%' . $query . '%';

        return $this->createQueryBuilder('c')
            ->where('LOWER(c.title) LIKE LOWER(:query)')
            ->orWhere('LOWER(c.content) LIKE LOWER(:query)')
            ->setParameter('query', $searchTerm)
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find contexts for composition (global + project-specific)
     * @return Context[]
     */
    public function findForComposition(?Project $project): array
    {
        $qb = $this->createQueryBuilder('c');

        if ($project !== null) {
            $qb->where('c.scope = :globalScope')
                ->orWhere('c.project = :project')
                ->setParameter('globalScope', Context::SCOPE_GLOBAL)
                ->setParameter('project', $project);
        } else {
            $qb->where('c.scope = :globalScope')
                ->setParameter('globalScope', Context::SCOPE_GLOBAL);
        }

        return $qb
            ->orderBy('c.scope', 'DESC') // project first, then global
            ->addOrderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all contexts ordered
     * @return Context[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.project', 'p')
            ->orderBy('c.scope', 'DESC')
            ->addOrderBy('p.name', 'ASC')
            ->addOrderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recent contexts
     * @return Context[]
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->addSelect('COALESCE(c.updatedAt, c.createdAt) AS HIDDEN lastModified')
            ->orderBy('lastModified', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
