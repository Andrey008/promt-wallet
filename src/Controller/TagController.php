<?php

namespace App\Controller;

use App\Entity\Tag;
use App\Entity\User;
use App\Repository\ContextRepository;
use App\Repository\PromptTemplateRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tags')]
class TagController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TagRepository $tagRepository,
        private ContextRepository $contextRepository,
        private PromptTemplateRepository $templateRepository
    ) {}

    #[Route('', name: 'tag_index', methods: ['GET'])]
    public function index(): Response
    {
        $tags = $this->tagRepository->findWithUsageCounts();

        return $this->render('tag/index.html.twig', [
            'tags' => $tags,
        ]);
    }

    #[Route('/{id}', name: 'tag_show', methods: ['GET'])]
    public function show(Tag $tag): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $contexts = $this->contextRepository->findByTag($tag, $user);
        $templates = $this->templateRepository->findByTag($tag, $user);

        return $this->render('tag/show.html.twig', [
            'tag' => $tag,
            'contexts' => $contexts,
            'templates' => $templates,
        ]);
    }

    #[Route('/{id}/delete', name: 'tag_delete', methods: ['POST'])]
    public function delete(Request $request, Tag $tag): Response
    {
        if ($this->isCsrfTokenValid('delete' . $tag->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($tag);
            $this->entityManager->flush();

            $this->addFlash('success', 'Tag deleted successfully.');
        }

        return $this->redirectToRoute('tag_index');
    }
}
