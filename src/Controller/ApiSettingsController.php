<?php

namespace App\Controller;

use App\Entity\ApiToken;
use App\Entity\User;
use App\Repository\ApiTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/settings/api-tokens')]
class ApiSettingsController extends AbstractController
{
    public function __construct(
        private ApiTokenRepository $apiTokenRepository,
        private EntityManagerInterface $em
    ) {}

    #[Route('', name: 'api_tokens_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $tokens = $this->apiTokenRepository->findAllByUser($user);

        return $this->render('api_settings/index.html.twig', [
            'tokens' => $tokens,
            'newToken' => null,
        ]);
    }

    #[Route('/create', name: 'api_tokens_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $name = trim($request->request->get('name', ''));
        if (empty($name)) {
            $this->addFlash('danger', 'Token name is required.');
            return $this->redirectToRoute('api_tokens_index');
        }

        $plainToken = ApiToken::generateToken();

        $apiToken = new ApiToken();
        $apiToken->setName($name);
        $apiToken->setToken($plainToken);
        $apiToken->setUser($user);

        $this->em->persist($apiToken);
        $this->em->flush();

        $tokens = $this->apiTokenRepository->findAllByUser($user);

        return $this->render('api_settings/index.html.twig', [
            'tokens' => $tokens,
            'newToken' => $plainToken,
            'newTokenName' => $name,
        ]);
    }

    #[Route('/{id}/revoke', name: 'api_tokens_revoke', methods: ['POST'])]
    public function revoke(int $id): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $token = $this->apiTokenRepository->find($id);
        if (!$token || $token->getUser() !== $user) {
            $this->addFlash('danger', 'Token not found.');
            return $this->redirectToRoute('api_tokens_index');
        }

        $token->setIsActive(false);
        $this->em->flush();

        $this->addFlash('success', 'API token "' . $token->getName() . '" has been revoked.');
        return $this->redirectToRoute('api_tokens_index');
    }
}
