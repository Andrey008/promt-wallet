<?php

namespace App\Controller\Api;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/projects')]
#[OA\Tag(name: 'Projects')]
class ProjectApiController extends AbstractApiController
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private EntityManagerInterface $em
    ) {}

    #[Route('', methods: ['GET'])]
    #[OA\Get(summary: 'List all projects')]
    #[OA\Response(response: 200, description: 'List of projects')]
    public function list(): JsonResponse
    {
        $projects = $this->projectRepository->findAllOrdered($this->getApiUser());

        return $this->jsonSuccess(array_map([$this, 'toArray'], $projects));
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(summary: 'Get a project')]
    #[OA\Response(response: 200, description: 'Project details')]
    public function show(int $id): JsonResponse
    {
        $project = $this->projectRepository->find($id);
        if (!$project || ($err = $this->checkOwnership($project))) {
            return $err ?? $this->jsonError('Not found.', Response::HTTP_NOT_FOUND);
        }

        return $this->jsonSuccess($this->toArray($project));
    }

    #[Route('', methods: ['POST'])]
    #[OA\Post(summary: 'Create a project')]
    #[OA\RequestBody(content: new OA\JsonContent(
        required: ['name'],
        properties: [
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'description', type: 'string', nullable: true),
            new OA\Property(property: 'stack', type: 'string', nullable: true, description: 'Comma-separated'),
        ]
    ))]
    #[OA\Response(response: 201, description: 'Project created')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $name = trim($data['name'] ?? '');

        if (empty($name)) {
            return $this->jsonError('Name is required.');
        }

        $project = new Project();
        $project->setName($name);
        $project->setDescription($data['description'] ?? null);
        $project->setStackFromString($data['stack'] ?? null);
        $project->setOwner($this->getApiUser());

        $this->em->persist($project);
        $this->em->flush();

        return $this->jsonSuccess($this->toArray($project), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[OA\Put(summary: 'Update a project')]
    #[OA\RequestBody(content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'description', type: 'string', nullable: true),
            new OA\Property(property: 'stack', type: 'string', nullable: true),
        ]
    ))]
    #[OA\Response(response: 200, description: 'Project updated')]
    public function update(int $id, Request $request): JsonResponse
    {
        $project = $this->projectRepository->find($id);
        if (!$project || ($err = $this->checkOwnership($project))) {
            return $err ?? $this->jsonError('Not found.', Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['name'])) {
            $name = trim($data['name']);
            if (empty($name)) {
                return $this->jsonError('Name cannot be empty.');
            }
            $project->setName($name);
        }
        if (array_key_exists('description', $data)) {
            $project->setDescription($data['description']);
        }
        if (array_key_exists('stack', $data)) {
            $project->setStackFromString($data['stack']);
        }

        $this->em->flush();

        return $this->jsonSuccess($this->toArray($project));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Delete a project')]
    #[OA\Response(response: 204, description: 'Project deleted')]
    public function delete(int $id): JsonResponse
    {
        $project = $this->projectRepository->find($id);
        if (!$project || ($err = $this->checkOwnership($project))) {
            return $err ?? $this->jsonError('Not found.', Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($project);
        $this->em->flush();

        return $this->jsonSuccess(null, Response::HTTP_NO_CONTENT);
    }

    private function toArray(Project $project): array
    {
        return [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
            'stack' => $project->getStack(),
            'createdAt' => $this->formatDateTime($project->getCreatedAt()),
            'updatedAt' => $this->formatDateTime($project->getUpdatedAt()),
        ];
    }
}
