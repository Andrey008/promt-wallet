<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\PromptCompositionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/compose/history')]
class CompositionHistoryController extends AbstractController
{
    public function __construct(
        private PromptCompositionRepository $compositionRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'composition_history_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $compositions = $this->compositionRepository->findByUser($user);

        return $this->render('compose/history_index.html.twig', [
            'compositions' => $compositions,
        ]);
    }

    #[Route('/{id}', name: 'composition_history_show', methods: ['GET'])]
    public function show(int $id): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $composition = $this->compositionRepository->find($id);

        if (!$composition || $composition->getOwner() !== $user) {
            throw $this->createNotFoundException('Composition not found.');
        }

        return $this->render('compose/history_show.html.twig', [
            'composition' => $composition,
        ]);
    }

    #[Route('/{id}/delete', name: 'composition_history_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $composition = $this->compositionRepository->find($id);

        if (!$composition || $composition->getOwner() !== $user) {
            throw $this->createNotFoundException('Composition not found.');
        }

        if ($this->isCsrfTokenValid('delete' . $composition->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($composition);
            $this->entityManager->flush();
            $this->addFlash('success', 'Composition deleted.');
        }

        return $this->redirectToRoute('composition_history_index');
    }
}
