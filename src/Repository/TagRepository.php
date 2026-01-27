<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tag>
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * Find all tags ordered by name
     * @return Tag[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find tag by name (case-insensitive)
     */
    public function findByName(string $name): ?Tag
    {
        return $this->createQueryBuilder('t')
            ->where('LOWER(t.name) = LOWER(:name)')
            ->setParameter('name', trim($name))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find or create tag by name
     */
    public function findOrCreate(string $name): Tag
    {
        $name = strtolower(trim($name));
        $tag = $this->findByName($name);

        if ($tag === null) {
            $tag = new Tag();
            $tag->setName($name);
            $this->getEntityManager()->persist($tag);
        }

        return $tag;
    }

    /**
     * Get tags with usage counts
     * @return array<array{tag: Tag, contextCount: int, templateCount: int}>
     */
    public function findWithUsageCounts(): array
    {
        return $this->createQueryBuilder('t')
            ->select('t')
            ->addSelect('SIZE(t.contexts) as contextCount')
            ->addSelect('SIZE(t.promptTemplates) as templateCount')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
