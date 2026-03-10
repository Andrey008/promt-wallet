<?php

namespace App\Repository;

use App\Entity\Snippet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Snippet>
 */
class SnippetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Snippet::class);
    }

    /**
     * @return Snippet[]
     */
    public function findAllOrdered(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('s.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Snippet[]
     */
    public function findRecent(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('COALESCE(s.updatedAt, s.createdAt)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Snippet[]
     */
    public function search(string $query, User $user): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.owner = :user')
            ->andWhere('LOWER(s.title) LIKE LOWER(:query) OR LOWER(s.content) LIKE LOWER(:query)')
            ->setParameter('user', $user)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('s.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
