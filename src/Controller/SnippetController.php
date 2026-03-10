<?php

namespace App\Controller;

use App\Entity\Snippet;
use App\Entity\User;
use App\Form\SnippetType;
use App\Repository\SnippetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/snippets')]
class SnippetController extends AbstractController
{
    public function __construct(
        private SnippetRepository $snippetRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'snippet_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('snippet/index.html.twig', [
            'snippets' => $this->snippetRepository->findAllOrdered($user),
        ]);
    }

    #[Route('/new', name: 'snippet_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $snippet = new Snippet();
        $form = $this->createForm(SnippetType::class, $snippet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $snippet->setOwner($user);
            $this->entityManager->persist($snippet);
            $this->entityManager->flush();

            $this->addFlash('success', 'Snippet created.');
            return $this->redirectToRoute('snippet_show', ['id' => $snippet->getId()]);
        }

        return $this->render('snippet/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/search', name: 'snippet_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $query = $request->query->get('q', '');
        $snippets = strlen($query) >= 2
            ? $this->snippetRepository->search($query, $user)
            : $this->snippetRepository->findAllOrdered($user);

        return $this->render('snippet/index.html.twig', [
            'snippets' => $snippets,
            'query' => $query,
        ]);
    }

    #[Route('/{id}', name: 'snippet_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        $snippet = $this->findOwnedSnippet($id);

        return $this->render('snippet/show.html.twig', [
            'snippet' => $snippet,
        ]);
    }

    #[Route('/{id}/edit', name: 'snippet_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $snippet = $this->findOwnedSnippet($id);

        $form = $this->createForm(SnippetType::class, $snippet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Snippet updated.');
            return $this->redirectToRoute('snippet_show', ['id' => $snippet->getId()]);
        }

        return $this->render('snippet/edit.html.twig', [
            'snippet' => $snippet,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'snippet_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $snippet = $this->findOwnedSnippet($id);

        if ($this->isCsrfTokenValid('delete' . $snippet->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($snippet);
            $this->entityManager->flush();
            $this->addFlash('success', 'Snippet deleted.');
        }

        return $this->redirectToRoute('snippet_index');
    }

    private function findOwnedSnippet(int $id): Snippet
    {
        /** @var User $user */
        $user = $this->getUser();

        $snippet = $this->snippetRepository->find($id);

        if (!$snippet || $snippet->getOwner() !== $user) {
            throw $this->createNotFoundException('Snippet not found.');
        }

        return $snippet;
    }
}
