<?php

namespace App\Controller;

use App\Entity\User;
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
        /** @var User $user */
        $user = $this->getUser();

        $projects = $this->projectRepository->findAllOrdered($user);
        $templates = $this->templateRepository->findAllOrdered($user);

        $selectedProjectId = $request->query->get('project');
        $selectedTemplateId = $request->query->get('template');
        $selectedContextIds = $request->query->all('context');

        $selectedProject = null;
        $availableContexts = $this->contextRepository->findGlobal($user);

        if ($selectedProjectId) {
            $selectedProject = $this->projectRepository->find($selectedProjectId);
            if ($selectedProject && $selectedProject->getOwner() === $user) {
                $availableContexts = $this->contextRepository->findForComposition($selectedProject, $user);
            } else {
                $selectedProject = null;
            }
        }

        $selectedTemplate = null;
        if ($selectedTemplateId) {
            $selectedTemplate = $this->templateRepository->find($selectedTemplateId);
            if ($selectedTemplate && $selectedTemplate->getOwner() !== $user) {
                $selectedTemplate = null;
            }
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
        /** @var User $user */
        $user = $this->getUser();

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
        if (!$template || $template->getOwner() !== $user) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Template not found.',
            ], 404);
        }

        $project = null;
        if ($projectId) {
            $project = $this->projectRepository->find($projectId);
            if ($project && $project->getOwner() !== $user) {
                $project = null;
            }
        }

        $contexts = [];
        if (!empty($contextIds)) {
            $contexts = $this->contextRepository->findBy(['id' => $contextIds, 'owner' => $user]);
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
        /** @var User $user */
        $user = $this->getUser();

        $projectId = $request->query->get('project');

        $project = null;
        if ($projectId) {
            $project = $this->projectRepository->find($projectId);
            if ($project && $project->getOwner() !== $user) {
                $project = null;
            }
        }

        $contexts = $this->contextRepository->findForComposition($project, $user);

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
