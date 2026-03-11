<?php

namespace App\Controller\Api;

use App\Entity\PromptTemplate;
use App\Repository\PromptTemplateRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/templates')]
#[OA\Tag(name: 'Templates')]
class TemplateApiController extends AbstractApiController
{
    public function __construct(
        private PromptTemplateRepository $templateRepository,
        private TagRepository $tagRepository,
        private EntityManagerInterface $em
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Get(summary: 'List all templates')]
    #[OA\Response(response: 200, description: 'List of templates')]
    public function list(): JsonResponse
    {
        $templates = $this->templateRepository->findAllOrdered($this->getApiUser());

        return $this->jsonSuccess(array_map([$this, 'toArray'], $templates));
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(summary: 'Get a template')]
    #[OA\Response(response: 200, description: 'Template details')]
    public function show(int $id): JsonResponse
    {
        $template = $this->templateRepository->find($id);
        if (!$template || ($err = $this->checkOwnership($template))) {
            return $err ?? $this->jsonError('Not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->jsonSuccess($this->toArray($template));
    }

    #[Route('', methods: ['POST'])]
    #[OA\Post(summary: 'Create a template')]
    #[OA\RequestBody(content: new OA\JsonContent(
        required: ['title', 'body'],
        properties: [
            new OA\Property(property: 'title', type: 'string'),
            new OA\Property(property: 'body', type: 'string'),
            new OA\Property(property: 'description', type: 'string', nullable: true),
            new OA\Property(property: 'targetModel', type: 'string', enum: ['universal', 'claude', 'cursor', 'copilot']),
            new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'string')),
        ]
    ))]
    #[OA\Response(response: 201, description: 'Template created')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $title = trim($data['title'] ?? '');
        $body = $data['body'] ?? '';

        if (empty($title) || empty($body)) {
            return $this->jsonError('Title and body are required.');
        }

        $template = new PromptTemplate();
        $template->setTitle($title);
        $template->setBody($body);
        $template->setDescription($data['description'] ?? null);
        $template->setTargetModel($data['targetModel'] ?? PromptTemplate::MODEL_UNIVERSAL);
        $template->setOwner($this->getApiUser());

        if (!empty($data['tags'])) {
            foreach ($data['tags'] as $tagName) {
                $tag = $this->tagRepository->findOrCreate($tagName);
                $template->addTag($tag);
            }
        }

        $this->em->persist($template);
        $this->em->flush();

        return $this->jsonSuccess($this->toArray($template), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(summary: 'Update a template')]
    #[OA\RequestBody(content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'title', type: 'string'),
            new OA\Property(property: 'body', type: 'string'),
            new OA\Property(property: 'description', type: 'string', nullable: true),
            new OA\Property(property: 'targetModel', type: 'string', enum: ['universal', 'claude', 'cursor', 'copilot']),
            new OA\Property(property: 'tags', type: 'array', items: new OA\Items(type: 'string')),
        ]
    ))]
    #[OA\Response(response: 200, description: 'Template updated')]
    public function update(int $id, Request $request): JsonResponse
    {
        $template = $this->templateRepository->find($id);
        if (!$template || ($err = $this->checkOwnership($template))) {
            return $err ?? $this->jsonError('Not found.', Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['title'])) {
            $title = trim($data['title']);
            if (empty($title)) {
                return $this->jsonError('Title cannot be empty.');
            }
            $template->setTitle($title);
        }
        if (isset($data['body'])) {
            $template->setBody($data['body']);
        }
        if (array_key_exists('description', $data)) {
            $template->setDescription($data['description']);
        }
        if (isset($data['targetModel'])) {
            $template->setTargetModel($data['targetModel']);
        }
        if (isset($data['tags'])) {
            $template->clearTags();
            foreach ($data['tags'] as $tagName) {
                $tag = $this->tagRepository->findOrCreate($tagName);
                $template->addTag($tag);
            }
        }

        $this->em->flush();

        return $this->jsonSuccess($this->toArray($template));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Delete a template')]
    #[OA\Response(response: 204, description: 'Template deleted')]
    public function delete(int $id): JsonResponse
    {
        $template = $this->templateRepository->find($id);
        if (!$template || ($err = $this->checkOwnership($template))) {
            return $err ?? $this->jsonError('Not found.', Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($template);
        $this->em->flush();

        return $this->jsonSuccess(null, Response::HTTP_NO_CONTENT);
    }

    private function toArray(PromptTemplate $template): array
    {
        return [
            'id' => $template->getId(),
            'title' => $template->getTitle(),
            'body' => $template->getBody(),
            'description' => $template->getDescription(),
            'targetModel' => $template->getTargetModel(),
            'placeholders' => $template->getPlaceholders(),
            'tags' => array_map(fn($t) => $t->getName(), $template->getTags()->toArray()),
            'createdAt' => $this->formatDateTime($template->getCreatedAt()),
            'updatedAt' => $this->formatDateTime($template->getUpdatedAt()),
        ];
    }
}
