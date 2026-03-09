<?php

namespace App\Repository;

use App\Entity\Context;
use App\Entity\Project;
use App\Entity\Tag;
use App\Entity\User;
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
     * @return Context[]
     */
    public function findGlobal(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.scope = :scope')
            ->andWhere('c.owner = :user')
            ->setParameter('scope', Context::SCOPE_GLOBAL)
            ->setParameter('user', $user)
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Context[]
     */
    public function findByProject(Project $project, User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.project = :project')
            ->andWhere('c.owner = :user')
            ->setParameter('project', $project)
            ->setParameter('user', $user)
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Context[]
     */
    public function findByTag(Tag $tag, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.tags', 't')
            ->where('t = :tag')
            ->setParameter('tag', $tag);

        if ($user !== null) {
            $qb->andWhere('c.owner = :user')
                ->setParameter('user', $user);
        }

        return $qb
            ->orderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Context[]
     */
    public function search(string $query, User $user): array
    {
        $searchTerm = '%' . $query . '%';

        return $this->createQueryBuilder('c')
            ->where('c.owner = :user')
            ->andWhere('LOWER(c.title) LIKE LOWER(:query) OR LOWER(c.content) LIKE LOWER(:query)')
            ->setParameter('user', $user)
            ->setParameter('query', $searchTerm)
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Context[]
     */
    public function findForComposition(?Project $project, User $user): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.owner = :user')
            ->setParameter('user', $user);

        if ($project !== null) {
            $qb->andWhere('c.scope = :globalScope OR c.project = :project')
                ->setParameter('globalScope', Context::SCOPE_GLOBAL)
                ->setParameter('project', $project);
        } else {
            $qb->andWhere('c.scope = :globalScope')
                ->setParameter('globalScope', Context::SCOPE_GLOBAL);
        }

        return $qb
            ->orderBy('c.scope', 'DESC')
            ->addOrderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Context[]
     */
    public function findAllOrdered(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.project', 'p')
            ->where('c.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('c.scope', 'DESC')
            ->addOrderBy('p.name', 'ASC')
            ->addOrderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Context[]
     */
    public function findRecent(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->addSelect('COALESCE(c.updatedAt, c.createdAt) AS HIDDEN lastModified')
            ->where('c.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('lastModified', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
