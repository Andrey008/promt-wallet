<?php

namespace App\Controller;

use App\Entity\PromptTemplate;
use App\Entity\User;
use App\Form\PromptTemplateType;
use App\Repository\PromptTemplateRepository;
use App\Service\PromptComposerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/templates')]
class PromptTemplateController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PromptTemplateRepository $templateRepository,
        private PromptComposerService $composerService
    ) {}

    #[Route('', name: 'template_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $model = $request->query->get('model');

        if ($model) {
            $templates = $this->templateRepository->findByTargetModel($model, $user);
        } else {
            $templates = $this->templateRepository->findAllOrdered($user);
        }

        return $this->render('template/index.html.twig', [
            'templates' => $templates,
            'models' => PromptTemplate::MODELS,
            'currentModel' => $model,
        ]);
    }

    #[Route('/new', name: 'template_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $template = new PromptTemplate();
        $form = $this->createForm(PromptTemplateType::class, $template);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $template->setOwner($user);
            $this->entityManager->persist($template);
            $this->entityManager->flush();

            $this->addFlash('success', 'Template created successfully.');

            return $this->redirectToRoute('template_show', ['id' => $template->getId()]);
        }

        return $this->render('template/new.html.twig', [
            'form' => $form,
            'placeholders' => $this->composerService->getAvailablePlaceholders(),
        ]);
    }

    #[Route('/search', name: 'template_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $query = $request->query->get('q', '');
        $templates = [];

        if (strlen($query) >= 2) {
            $templates = $this->templateRepository->search($query, $user);
        }

        return $this->render('template/search.html.twig', [
            'templates' => $templates,
            'query' => $query,
        ]);
    }

    #[Route('/{id}', name: 'template_show', methods: ['GET'])]
    public function show(PromptTemplate $template): Response
    {
        $this->denyAccessUnlessOwner($template);

        $preview = $this->composerService->previewTemplate($template);

        return $this->render('template/show.html.twig', [
            'template' => $template,
            'preview' => $preview,
            'placeholders' => $template->getPlaceholders(),
        ]);
    }

    #[Route('/{id}/edit', name: 'template_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PromptTemplate $template): Response
    {
        $this->denyAccessUnlessOwner($template);

        $form = $this->createForm(PromptTemplateType::class, $template);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Template updated successfully.');

            return $this->redirectToRoute('template_show', ['id' => $template->getId()]);
        }

        return $this->render('template/edit.html.twig', [
            'template' => $template,
            'form' => $form,
            'placeholders' => $this->composerService->getAvailablePlaceholders(),
        ]);
    }

    #[Route('/{id}/delete', name: 'template_delete', methods: ['POST'])]
    public function delete(Request $request, PromptTemplate $template): Response
    {
        $this->denyAccessUnlessOwner($template);

        if ($this->isCsrfTokenValid('delete' . $template->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($template);
            $this->entityManager->flush();

            $this->addFlash('success', 'Template deleted successfully.');
        }

        return $this->redirectToRoute('template_index');
    }

    private function denyAccessUnlessOwner(PromptTemplate $template): void
    {
        if ($template->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
    }
}
