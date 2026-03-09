<?php

namespace App\Controller;

use App\Entity\Context;
use App\Entity\User;
use App\Form\ContextType;
use App\Repository\ContextRepository;
use App\Repository\ProjectRepository;
use App\Service\MarkdownService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contexts')]
class ContextController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ContextRepository $contextRepository,
        private ProjectRepository $projectRepository,
        private MarkdownService $markdownService
    ) {}

    #[Route('', name: 'context_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $scope = $request->query->get('scope');
        $projectId = $request->query->get('project');

        if ($scope === 'global') {
            $contexts = $this->contextRepository->findGlobal($user);
        } elseif ($projectId) {
            $project = $this->projectRepository->find($projectId);
            if ($project && $project->getOwner() === $user) {
                $contexts = $this->contextRepository->findByProject($project, $user);
            } else {
                $contexts = [];
            }
        } else {
            $contexts = $this->contextRepository->findAllOrdered($user);
        }

        $projects = $this->projectRepository->findAllOrdered($user);

        return $this->render('context/index.html.twig', [
            'contexts' => $contexts,
            'projects' => $projects,
            'currentScope' => $scope,
            'currentProject' => $projectId,
        ]);
    }

    #[Route('/new', name: 'context_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $context = new Context();

        $projectId = $request->query->get('project');
        if ($projectId) {
            $project = $this->projectRepository->find($projectId);
            if ($project && $project->getOwner() === $user) {
                $context->setProject($project);
                $context->setScope(Context::SCOPE_PROJECT);
            }
        }

        $form = $this->createForm(ContextType::class, $context, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $context->setOwner($user);
            $this->entityManager->persist($context);
            $this->entityManager->flush();

            $this->addFlash('success', 'Context created successfully.');

            return $this->redirectToRoute('context_show', ['id' => $context->getId()]);
        }

        return $this->render('context/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/search', name: 'context_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $query = $request->query->get('q', '');
        $contexts = [];

        if (strlen($query) >= 2) {
            $contexts = $this->contextRepository->search($query, $user);
        }

        return $this->render('context/search.html.twig', [
            'contexts' => $contexts,
            'query' => $query,
        ]);
    }

    #[Route('/{id}', name: 'context_show', methods: ['GET'])]
    public function show(Context $context): Response
    {
        $this->denyAccessUnlessOwner($context);

        $htmlContent = $this->markdownService->toHtml($context->getContent());

        return $this->render('context/show.html.twig', [
            'context' => $context,
            'htmlContent' => $htmlContent,
        ]);
    }

    #[Route('/{id}/edit', name: 'context_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Context $context): Response
    {
        $this->denyAccessUnlessOwner($context);

        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(ContextType::class, $context, ['user' => $user]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Context updated successfully.');

            return $this->redirectToRoute('context_show', ['id' => $context->getId()]);
        }

        return $this->render('context/edit.html.twig', [
            'context' => $context,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'context_delete', methods: ['POST'])]
    public function delete(Request $request, Context $context): Response
    {
        $this->denyAccessUnlessOwner($context);

        if ($this->isCsrfTokenValid('delete' . $context->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($context);
            $this->entityManager->flush();

            $this->addFlash('success', 'Context deleted successfully.');
        }

        return $this->redirectToRoute('context_index');
    }

    private function denyAccessUnlessOwner(Context $context): void
    {
        if ($context->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
    }
}
