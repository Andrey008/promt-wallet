<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * @return Project[]
     */
    public function findAllOrdered(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<array{project: Project, contextCount: int}>
     */
    public function findWithContextCount(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->select('p')
            ->addSelect('SIZE(p.contexts) as contextCount')
            ->where('p.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Project[]
     */
    public function findRecent(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('COALESCE(p.updatedAt, p.createdAt) AS HIDDEN lastModified')
            ->where('p.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('lastModified', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
