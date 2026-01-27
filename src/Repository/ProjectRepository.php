<?php

namespace App\Repository;

use App\Entity\Project;
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
     * Find all projects ordered by name
     * @return Project[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find projects with context count
     * @return array<array{project: Project, contextCount: int}>
     */
    public function findWithContextCount(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p')
            ->addSelect('SIZE(p.contexts) as contextCount')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recently updated projects
     * @return Project[]
     */
    public function findRecent(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('COALESCE(p.updatedAt, p.createdAt) AS HIDDEN lastModified')
            ->orderBy('lastModified', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
