<?php

namespace App\Service;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;

class TagService
{
    public function __construct(
        private TagRepository $tagRepository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Parse comma-separated tags string and return Tag entities
     * Creates new tags if they don't exist
     *
     * @return Tag[]
     */
    public function parseAndCreate(string $tagsString): array
    {
        if (empty(trim($tagsString))) {
            return [];
        }

        $tagNames = array_map('trim', explode(',', $tagsString));
        $tagNames = array_filter($tagNames, fn($name) => !empty($name));
        $tagNames = array_unique(array_map('strtolower', $tagNames));

        $tags = [];
        foreach ($tagNames as $name) {
            $tags[] = $this->findOrCreate($name);
        }

        return $tags;
    }

    /**
     * Get or create tag by name
     */
    public function findOrCreate(string $name): Tag
    {
        $name = strtolower(trim($name));
        $tag = $this->tagRepository->findByName($name);

        if ($tag === null) {
            $tag = new Tag();
            $tag->setName($name);
            $tag->setColor($this->generateColor($name));
            $this->entityManager->persist($tag);
        }

        return $tag;
    }

    /**
     * Convert tags collection to comma-separated string
     *
     * @param iterable<Tag> $tags
     */
    public function tagsToString(iterable $tags): string
    {
        $names = [];
        foreach ($tags as $tag) {
            $names[] = $tag->getName();
        }
        return implode(', ', $names);
    }

    /**
     * Generate consistent color based on tag name
     */
    private function generateColor(string $name): string
    {
        $colors = [
            '#0d6efd', // blue
            '#6610f2', // indigo
            '#6f42c1', // purple
            '#d63384', // pink
            '#dc3545', // red
            '#fd7e14', // orange
            '#ffc107', // yellow
            '#198754', // green
            '#20c997', // teal
            '#0dcaf0', // cyan
        ];

        $hash = crc32($name);
        $index = abs($hash) % count($colors);

        return $colors[$index];
    }
}
