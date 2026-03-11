<?php

namespace App\Controller\Api;

use App\Repository\TagRepository;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/tags')]
#[OA\Tag(name: 'Tags')]
class TagApiController extends AbstractApiController
{
    public function __construct(
        private TagRepository $tagRepository
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Get(summary: 'List all tags')]
    #[OA\Response(response: 200, description: 'List of tags')]
    public function list(): JsonResponse
    {
        $tags = $this->tagRepository->findWithUsageCounts();

        $result = [];
        foreach ($tags as $row) {
            $tag = $row[0];
            $result[] = [
                'id' => $tag->getId(),
                'name' => $tag->getName(),
                'color' => $tag->getColor(),
                'contextCount' => $row['contextCount'],
                'templateCount' => $row['templateCount'],
                'createdAt' => $this->formatDateTime($tag->getCreatedAt()),
            ];
        }

        return $this->jsonSuccess($result);
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(summary: 'Get a tag')]
    #[OA\Response(response: 200, description: 'Tag details')]
    public function show(int $id): JsonResponse
    {
        $tag = $this->tagRepository->find($id);
        if (!$tag) {
            return $this->jsonError('Not found.', 404);
        }

        return $this->jsonSuccess([
            'id' => $tag->getId(),
            'name' => $tag->getName(),
            'color' => $tag->getColor(),
            'createdAt' => $this->formatDateTime($tag->getCreatedAt()),
        ]);
    }
}
