<?php

namespace App\Controller\Api;

use App\Entity\Snippet;
use App\Repository\SnippetRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/snippets')]
#[OA\Tag(name: 'Snippets')]
class SnippetApiController extends AbstractApiController
{
    public function __construct(
        private SnippetRepository $snippetRepository,
        private EntityManagerInterface $em
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Get(summary: 'List all snippets')]
    #[OA\Response(response: 200, description: 'List of snippets')]
    public function list(): JsonResponse
    {
        $snippets = $this->snippetRepository->findAllOrdered($this->getApiUser());

        return $this->jsonSuccess(array_map([$this, 'toArray'], $snippets));
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(summary: 'Get a snippet')]
    #[OA\Response(response: 200, description: 'Snippet details')]
    public function show(int $id): JsonResponse
    {
        $snippet = $this->snippetRepository->find($id);
        if (!$snippet || ($err = $this->checkOwnership($snippet))) {
            return $err ?? $this->jsonError('Not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->jsonSuccess($this->toArray($snippet));
    }

    #[Route('', methods: ['POST'])]
    #[OA\Post(summary: 'Create a snippet')]
    #[OA\RequestBody(content: new OA\JsonContent(
        required: ['title', 'content'],
        properties: [
            new OA\Property(property: 'title', type: 'string'),
            new OA\Property(property: 'content', type: 'string'),
        ]
    ))]
    #[OA\Response(response: 201, description: 'Snippet created')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $title = trim($data['title'] ?? '');
        $content = $data['content'] ?? '';

        if (empty($title) || empty($content)) {
            return $this->jsonError('Title and content are required.');
        }

        $snippet = new Snippet();
        $snippet->setTitle($title);
        $snippet->setContent($content);
        $snippet->setOwner($this->getApiUser());

        $this->em->persist($snippet);
        $this->em->flush();

        return $this->jsonSuccess($this->toArray($snippet), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(summary: 'Update a snippet')]
    #[OA\RequestBody(content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'title', type: 'string'),
            new OA\Property(property: 'content', type: 'string'),
        ]
    ))]
    #[OA\Response(response: 200, description: 'Snippet updated')]
    public function update(int $id, Request $request): JsonResponse
    {
        $snippet = $this->snippetRepository->find($id);
        if (!$snippet || ($err = $this->checkOwnership($snippet))) {
            return $err ?? $this->jsonError('Not found.', Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['title'])) {
            $title = trim($data['title']);
            if (empty($title)) {
                return $this->jsonError('Title cannot be empty.');
            }
            $snippet->setTitle($title);
        }
        if (isset($data['content'])) {
            $snippet->setContent($data['content']);
        }

        $this->em->flush();

        return $this->jsonSuccess($this->toArray($snippet));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Delete a snippet')]
    #[OA\Response(response: 204, description: 'Snippet deleted')]
    public function delete(int $id): JsonResponse
    {
        $snippet = $this->snippetRepository->find($id);
        if (!$snippet || ($err = $this->checkOwnership($snippet))) {
            return $err ?? $this->jsonError('Not found.', Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($snippet);
        $this->em->flush();

        return $this->jsonSuccess(null, Response::HTTP_NO_CONTENT);
    }

    private function toArray(Snippet $snippet): array
    {
        return [
            'id' => $snippet->getId(),
            'title' => $snippet->getTitle(),
            'content' => $snippet->getContent(),
            'createdAt' => $this->formatDateTime($snippet->getCreatedAt()),
            'updatedAt' => $this->formatDateTime($snippet->getUpdatedAt()),
        ];
    }
}
