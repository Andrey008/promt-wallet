<?php

namespace App\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractApiController extends AbstractController
{
    protected function jsonSuccess(mixed $data, int $status = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse($data, $status);
    }

    protected function jsonError(string $message, int $status = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return new JsonResponse(['error' => $message], $status);
    }

    protected function getApiUser(): User
    {
        /** @var User $user */
        $user = $this->getUser();
        return $user;
    }

    protected function checkOwnership(object $entity): ?JsonResponse
    {
        if (!method_exists($entity, 'getOwner')) {
            return null;
        }

        if ($entity->getOwner() !== $this->getApiUser()) {
            return $this->jsonError('Not found.', Response::HTTP_NOT_FOUND);
        }

        return null;
    }

    protected function formatDateTime(?\DateTimeImmutable $date): ?string
    {
        return $date?->format('c');
    }
}
