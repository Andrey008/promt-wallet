<?php

namespace App\Repository;

use App\Entity\PromptComposition;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PromptComposition>
 */
class PromptCompositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PromptComposition::class);
    }

    /**
     * @return PromptComposition[]
     */
    public function findByUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return PromptComposition[]
     */
    public function findRecent(User $user, int $limit = 10): array
    {
        return $this->findByUser($user, $limit);
    }
}
