<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ContextRepository;
use App\Repository\ProjectRepository;
use App\Repository\PromptTemplateRepository;
use App\Repository\SnippetRepository;
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
        private TagRepository $tagRepository,
        private SnippetRepository $snippetRepository
    ) {}

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $recentProjects = $this->projectRepository->findRecent($user, 5);
        $recentContexts = $this->contextRepository->findRecent($user, 5);
        $recentTemplates = $this->templateRepository->findRecent($user, 5);

        $stats = [
            'projects' => count($this->projectRepository->findAllOrdered($user)),
            'contexts' => count($this->contextRepository->findAllOrdered($user)),
            'templates' => count($this->templateRepository->findAllOrdered($user)),
            'tags' => count($this->tagRepository->findAll()),
            'snippets' => count($this->snippetRepository->findAllOrdered($user)),
        ];

        return $this->render('home/index.html.twig', [
            'recentProjects' => $recentProjects,
            'recentContexts' => $recentContexts,
            'recentTemplates' => $recentTemplates,
            'stats' => $stats,
        ]);
    }
}
