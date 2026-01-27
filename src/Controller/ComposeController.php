<?php

namespace App\Controller;

use App\Repository\ContextRepository;
use App\Repository\ProjectRepository;
use App\Repository\PromptTemplateRepository;
use App\Service\PromptComposerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/compose')]
class ComposeController extends AbstractController
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private ContextRepository $contextRepository,
        private PromptTemplateRepository $templateRepository,
        private PromptComposerService $composerService
    ) {}

    #[Route('', name: 'compose_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $projects = $this->projectRepository->findAllOrdered();
        $templates = $this->templateRepository->findAllOrdered();

        // Pre-select from query params
        $selectedProjectId = $request->query->get('project');
        $selectedTemplateId = $request->query->get('template');
        $selectedContextIds = $request->query->all('context');

        $selectedProject = null;
        $availableContexts = $this->contextRepository->findGlobal();

        if ($selectedProjectId) {
            $selectedProject = $this->projectRepository->find($selectedProjectId);
            if ($selectedProject) {
                $availableContexts = $this->contextRepository->findForComposition($selectedProject);
            }
        }

        $selectedTemplate = null;
        if ($selectedTemplateId) {
            $selectedTemplate = $this->templateRepository->find($selectedTemplateId);
        }

        return $this->render('compose/index.html.twig', [
            'projects' => $projects,
            'templates' => $templates,
            'availableContexts' => $availableContexts,
            'selectedProject' => $selectedProject,
            'selectedTemplate' => $selectedTemplate,
            'selectedContextIds' => $selectedContextIds,
            'placeholders' => $this->composerService->getAvailablePlaceholders(),
        ]);
    }

    #[Route('/render', name: 'compose_render', methods: ['POST'])]
    public function renderComposition(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $projectId = $data['project'] ?? null;
        $templateId = $data['template'] ?? null;
        $contextIds = $data['contexts'] ?? [];

        if (empty($templateId)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Please select a template.',
            ], 400);
        }

        $template = $this->templateRepository->find($templateId);
        if (!$template) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Template not found.',
            ], 404);
        }

        $project = null;
        if ($projectId) {
            $project = $this->projectRepository->find($projectId);
        }

        $contexts = [];
        if (!empty($contextIds)) {
            $contexts = $this->contextRepository->findBy(['id' => $contextIds]);
            // Sort by sortOrder
            usort($contexts, fn($a, $b) => $a->getSortOrder() <=> $b->getSortOrder());
        }

        $result = $this->composerService->compose($template, $contexts, $project);

        return new JsonResponse([
            'success' => true,
            'result' => $result,
            'length' => strlen($result),
            'contextCount' => count($contexts),
        ]);
    }

    #[Route('/contexts', name: 'compose_contexts', methods: ['GET'])]
    public function getContexts(Request $request): JsonResponse
    {
        $projectId = $request->query->get('project');

        $project = null;
        if ($projectId) {
            $project = $this->projectRepository->find($projectId);
        }

        $contexts = $this->contextRepository->findForComposition($project);

        $data = [];
        foreach ($contexts as $context) {
            $data[] = [
                'id' => $context->getId(),
                'title' => $context->getTitle(),
                'scope' => $context->getScope(),
                'project' => $context->getProject()?->getName(),
                'sortOrder' => $context->getSortOrder(),
            ];
        }

        return new JsonResponse($data);
    }
}
