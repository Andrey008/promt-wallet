<?php

namespace App\Controller\Api;

use App\Entity\PromptComposition;
use App\Repository\ContextRepository;
use App\Repository\ProjectRepository;
use App\Repository\PromptTemplateRepository;
use App\Repository\SnippetRepository;
use App\Service\PromptComposerService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/compose')]
#[OA\Tag(name: 'Compose')]
class ComposeApiController extends AbstractApiController
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private ContextRepository $contextRepository,
        private PromptTemplateRepository $templateRepository,
        private SnippetRepository $snippetRepository,
        private PromptComposerService $composerService,
        private EntityManagerInterface $em
    ) {}

    #[Route('', methods: ['POST'])]
    #[OA\Post(summary: 'Compose a prompt from template, contexts, and snippets')]
    #[OA\RequestBody(content: new OA\JsonContent(
        required: ['templateId'],
        properties: [
            new OA\Property(property: 'templateId', type: 'integer'),
            new OA\Property(property: 'projectId', type: 'integer', nullable: true),
            new OA\Property(property: 'contextIds', type: 'array', items: new OA\Items(type: 'integer')),
            new OA\Property(property: 'snippetIds', type: 'array', items: new OA\Items(type: 'integer')),
        ]
    ))]
    #[OA\Response(response: 200, description: 'Composed prompt')]
    public function compose(Request $request): JsonResponse
    {
        $user = $this->getApiUser();
        $data = json_decode($request->getContent(), true) ?? [];

        $templateId = $data['templateId'] ?? null;
        if (empty($templateId)) {
            return $this->jsonError('templateId is required.');
        }

        $template = $this->templateRepository->find($templateId);
        if (!$template || $template->getOwner() !== $user) {
            return $this->jsonError('Template not found.', Response::HTTP_NOT_FOUND);
        }

        $project = null;
        if (!empty($data['projectId'])) {
            $project = $this->projectRepository->find($data['projectId']);
            if ($project && $project->getOwner() !== $user) {
                $project = null;
            }
        }

        $contexts = [];
        if (!empty($data['contextIds'])) {
            $contexts = $this->contextRepository->findBy(['id' => $data['contextIds'], 'owner' => $user]);
            usort($contexts, fn($a, $b) => $a->getSortOrder() <=> $b->getSortOrder());
        }

        $snippets = [];
        if (!empty($data['snippetIds'])) {
            $snippets = $this->snippetRepository->findBy(['id' => $data['snippetIds'], 'owner' => $user]);
        }

        $result = $this->composerService->compose($template, $contexts, $project, $snippets);

        $composition = new PromptComposition();
        $composition->setComposedText($result);
        $composition->setTemplateTitle($template->getTitle());
        $composition->setProjectName($project?->getName());
        $composition->setContextTitles(array_merge(
            array_map(fn($c) => $c->getTitle(), $contexts),
            array_map(fn($s) => $s->getTitle(), $snippets)
        ));
        $composition->setOwner($user);

        $this->em->persist($composition);
        $this->em->flush();

        return $this->jsonSuccess([
            'result' => $result,
            'length' => strlen($result),
            'contextCount' => count($contexts),
            'snippetCount' => count($snippets),
        ]);
    }
}
