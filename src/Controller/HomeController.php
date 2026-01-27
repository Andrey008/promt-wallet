<?php

namespace App\Controller;

use App\Repository\ContextRepository;
use App\Repository\ProjectRepository;
use App\Repository\PromptTemplateRepository;
use App\Repository\TagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private ContextRepository $contextRepository,
        private PromptTemplateRepository $templateRepository,
        private TagRepository $tagRepository
    ) {}

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $recentProjects = $this->projectRepository->findRecent(5);
        $recentContexts = $this->contextRepository->findRecent(5);
        $recentTemplates = $this->templateRepository->findRecent(5);

        $stats = [
            'projects' => count($this->projectRepository->findAll()),
            'contexts' => count($this->contextRepository->findAll()),
            'templates' => count($this->templateRepository->findAll()),
            'tags' => count($this->tagRepository->findAll()),
        ];

        return $this->render('home/index.html.twig', [
            'recentProjects' => $recentProjects,
            'recentContexts' => $recentContexts,
            'recentTemplates' => $recentTemplates,
            'stats' => $stats,
        ]);
    }
}
