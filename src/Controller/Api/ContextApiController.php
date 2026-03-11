<?php

namespace App\Controller\Api;

use App\Entity\Context;
use App\Repository\ContextRepository;
use App\Repository\ProjectRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/contexts')]
#[OA\Tag(name: 'Contexts')]
class ContextApiController extends AbstractApiController
{
    public function __construct(
        private ContextRepository $contextRepository,
        private ProjectRepository $projectRepository,
        private TagRepository $tagRepository,
        private EntityManagerInterface $em
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Get(summary: 'List all contexts')]
    #[OA\Response(response: 200, description: 'List of contexts')]
    public function list(): JsonResponse
    {
        $contexts = $this->contextRepository->findAllOrdered($this->getApiUser());

        return $this->jsonSuccess(array_map([$this, 'toArray'], $contexts));
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(summary: 'Get a context')]
    #[OA\Response(response: 200, description: 'Context details')]
    public function show(int $id): JsonResponse
    {
        $context = $this->contextRepository->find($id);
        if (!$context || ($err = $this->checkOwnership($context))) {
            return $err ?? $this->jsonError('Not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->jsonSuccess($this->toArray($context));
    }

    #[Route('', methods: ['POST'])]
    #[OA\Post(summary: 'Create a context')]
    #[OA\RequestBody(content: new OA\JsonContent(
        required: ['title', 'content'],
        properties: [
            new OA\Property(property: 'title', type: 'string'),
            new OA\Property(property: 'content', type: 'string'),
            new OA\Property(property: 'scope', type: 'string', enum: ['global', 'project']),
            new OA\Property(property: 'projectId', type: 'integer', nullable: true),
            new OA\Property(property: 'sortOrder', type: 'integer'),
            new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'string'), description: 'Tag names'),
        ]
    ))]
    #[OA\Response(response: 201, description: 'Context created')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $title = trim($data['title'] ?? '');
        $content = $data['content'] ?? '';

        if (empty($title) || empty($content)) {
            return $this->jsonError('Title and content are required.');
        }

        $context = new Context();
        $context->setTitle($title);
        $context->setContent($content);
        $context->setScope($data['scope'] ?? Context::SCOPE_GLOBAL);
        $context->setSortOrder($data['sortOrder'] ?? 0);
        $context->setOwner($this->getApiUser());

        if (!empty($data['projectId'])) {
            $project = $this->projectRepository->find($data['projectId']);
            if ($project && $project->getOwner() === $this->getApiUser()) {
                $context->setProject($project);
            }
        }

        if (!empty($data['tags'])) {
            foreach ($data['tags'] as $tagName) {
                $tag = $this->tagRepository->findOrCreate($tagName);
                $context->addTag($tag);
            }
        }

        $this->em->persist($context);
        $this->em->flush();

        return $this->jsonSuccess($this->toArray($context), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(summary: 'Update a context')]
    #[OA\RequestBody(content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'title', type: 'string'),
            new OA\Property(property: 'content', type: 'string'),
            new OA\Property(property: 'scope', type: 'string', enum: ['global', 'project']),
            new OA\Property(property: 'projectId', type: 'integer', nullable: true),
            new OA\Property(property: 'sortOrder', type: 'integer'),
            new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'string')),
        ]
    ))]
    #[OA\Response(response: 200, description: 'Context updated')]
    public function update(int $id, Request $request): JsonResponse
    {
        $context = $this->contextRepository->find($id);
        if (!$context || ($err = $this->checkOwnership($context))) {
            return $err ?? $this->jsonError('Not found.', Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['title'])) {
            $title = trim($data['title']);
            if (empty($title)) {
                return $this->jsonError('Title cannot be empty.');
            }
            $context->setTitle($title);
        }
        if (isset($data['content'])) {
            $context->setContent($data['content']);
        }
        if (isset($data['scope'])) {
            $context->setScope($data['scope']);
        }
        if (isset($data['sortOrder'])) {
            $context->setSortOrder($data['sortOrder']);
        }
        if (array_key_exists('projectId', $data)) {
            if ($data['projectId']) {
                $project = $this->projectRepository->find($data['projectId']);
                if ($project && $project->getOwner() === $this->getApiUser()) {
                    $context->setProject($project);
                }
            } else {
                $context->setProject(null);
            }
        }
        if (isset($data['tags'])) {
            $context->clearTags();
            foreach ($data['tags'] as $tagName) {
                $tag = $this->tagRepository->findOrCreate($tagName);
                $context->addTag($tag);
            }
        }

        $this->em->flush();

        return $this->jsonSuccess($this->toArray($context));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Delete a context')]
    #[OA\Response(response: 204, description: 'Context deleted')]
    public function delete(int $id): JsonResponse
    {
        $context = $this->contextRepository->find($id);
        if (!$context || ($err = $this->checkOwnership($context))) {
            return $err ?? $this->jsonError('Not found.', Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($context);
        $this->em->flush();

        return $this->jsonSuccess(null, Response::HTTP_NO_CONTENT);
    }

    private function toArray(Context $context): array
    {
        return [
            'id' => $context->getId(),
            'title' => $context->getTitle(),
            'content' => $context->getContent(),
            'scope' => $context->getScope(),
            'projectId' => $context->getProject()?->getId(),
            'projectName' => $context->getProject()?->getName(),
            'sortOrder' => $context->getSortOrder(),
            'tags' => array_map(fn($t) => $t->getName(), $context->getTags()->toArray()),
            'createdAt' => $this->formatDateTime($context->getCreatedAt()),
            'updatedAt' => $this->formatDateTime($context->getUpdatedAt()),
        ];
    }
}
