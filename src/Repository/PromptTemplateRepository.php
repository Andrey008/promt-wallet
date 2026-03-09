<?php

namespace App\Repository;

use App\Entity\PromptTemplate;
use App\Entity\Tag;
use App\Entity\User;
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
     * @return PromptTemplate[]
     */
    public function findAllOrdered(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('t.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return PromptTemplate[]
     */
    public function findByTargetModel(string $model, User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.targetModel = :model')
            ->andWhere('t.owner = :user')
            ->setParameter('model', $model)
            ->setParameter('user', $user)
            ->orderBy('t.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return PromptTemplate[]
     */
    public function findByTag(Tag $tag, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->innerJoin('t.tags', 'tag')
            ->where('tag = :tag')
            ->setParameter('tag', $tag);

        if ($user !== null) {
            $qb->andWhere('t.owner = :user')
                ->setParameter('user', $user);
        }

        return $qb
            ->orderBy('t.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return PromptTemplate[]
     */
    public function search(string $query, User $user): array
    {
        $searchTerm = '%' . $query . '%';

        return $this->createQueryBuilder('t')
            ->where('t.owner = :user')
            ->andWhere('LOWER(t.title) LIKE LOWER(:query) OR LOWER(t.body) LIKE LOWER(:query) OR LOWER(t.description) LIKE LOWER(:query)')
            ->setParameter('user', $user)
            ->setParameter('query', $searchTerm)
            ->orderBy('t.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return PromptTemplate[]
     */
    public function findRecent(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->addSelect('COALESCE(t.updatedAt, t.createdAt) AS HIDDEN lastModified')
            ->where('t.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('lastModified', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
