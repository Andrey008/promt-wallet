<?php

namespace App\Service;

use App\Entity\Context;
use App\Entity\Project;
use App\Entity\PromptTemplate;
use App\Entity\Snippet;
use App\Entity\User;
use App\Repository\ContextRepository;
use App\Repository\ProjectRepository;
use App\Repository\PromptTemplateRepository;
use App\Repository\SnippetRepository;
use Doctrine\ORM\EntityManagerInterface;

class ImportExportService
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private ContextRepository $contextRepository,
        private PromptTemplateRepository $templateRepository,
        private SnippetRepository $snippetRepository,
        private TagService $tagService,
        private EntityManagerInterface $entityManager
    ) {}

    public function exportAll(User $user): array
    {
        $data = [
            'version' => 1,
            'exportedAt' => date('Y-m-d\TH:i:sP'),
            'projects' => [],
            'contexts' => [],
            'templates' => [],
            'snippets' => [],
        ];

        foreach ($this->projectRepository->findAllOrdered($user) as $project) {
            $data['projects'][] = [
                'name' => $project->getName(),
                'description' => $project->getDescription(),
                'stack' => $project->getStack(),
            ];
        }

        foreach ($this->contextRepository->findAllOrdered($user) as $context) {
            $data['contexts'][] = [
                'title' => $context->getTitle(),
                'content' => $context->getContent(),
                'scope' => $context->getScope(),
                'projectName' => $context->getProject()?->getName(),
                'sortOrder' => $context->getSortOrder(),
                'tags' => array_map(fn($t) => $t->getName(), $context->getTags()->toArray()),
            ];
        }

        foreach ($this->templateRepository->findAllOrdered($user) as $template) {
            $data['templates'][] = [
                'title' => $template->getTitle(),
                'body' => $template->getBody(),
                'targetModel' => $template->getTargetModel(),
                'description' => $template->getDescription(),
                'tags' => array_map(fn($t) => $t->getName(), $template->getTags()->toArray()),
            ];
        }

        foreach ($this->snippetRepository->findAllOrdered($user) as $snippet) {
            $data['snippets'][] = [
                'title' => $snippet->getTitle(),
                'content' => $snippet->getContent(),
            ];
        }

        return $data;
    }

    public function importAll(User $user, array $data, string $strategy = 'skip'): array
    {
        $result = ['created' => 0, 'skipped' => 0, 'updated' => 0, 'errors' => []];

        // Import projects first (contexts may reference them)
        $projectMap = [];
        foreach ($this->projectRepository->findAllOrdered($user) as $p) {
            $projectMap[$p->getName()] = $p;
        }

        foreach ($data['projects'] ?? [] as $pData) {
            $name = $pData['name'] ?? '';
            if (empty($name)) continue;

            if (isset($projectMap[$name])) {
                if ($strategy === 'overwrite') {
                    $project = $projectMap[$name];
                    $project->setDescription($pData['description'] ?? null);
                    $project->setStack($pData['stack'] ?? null);
                    $result['updated']++;
                } else {
                    $result['skipped']++;
                }
            } else {
                $project = new Project();
                $project->setName($name);
                $project->setDescription($pData['description'] ?? null);
                $project->setStack($pData['stack'] ?? null);
                $project->setOwner($user);
                $this->entityManager->persist($project);
                $projectMap[$name] = $project;
                $result['created']++;
            }
        }

        $this->entityManager->flush();

        // Import contexts
        $existingContexts = [];
        foreach ($this->contextRepository->findAllOrdered($user) as $c) {
            $existingContexts[$c->getTitle()] = $c;
        }

        foreach ($data['contexts'] ?? [] as $cData) {
            $title = $cData['title'] ?? '';
            if (empty($title)) continue;

            if (isset($existingContexts[$title])) {
                if ($strategy === 'overwrite') {
                    $context = $existingContexts[$title];
                    $context->setContent($cData['content'] ?? '');
                    $context->setScope($cData['scope'] ?? Context::SCOPE_GLOBAL);
                    $context->setSortOrder($cData['sortOrder'] ?? 0);
                    if (!empty($cData['projectName']) && isset($projectMap[$cData['projectName']])) {
                        $context->setProject($projectMap[$cData['projectName']]);
                    }
                    $context->clearTags();
                    foreach ($cData['tags'] ?? [] as $tagName) {
                        $context->addTag($this->tagService->findOrCreate($tagName));
                    }
                    $result['updated']++;
                } else {
                    $result['skipped']++;
                }
            } else {
                $context = new Context();
                $context->setTitle($title);
                $context->setContent($cData['content'] ?? '');
                $context->setScope($cData['scope'] ?? Context::SCOPE_GLOBAL);
                $context->setSortOrder($cData['sortOrder'] ?? 0);
                $context->setOwner($user);
                if (!empty($cData['projectName']) && isset($projectMap[$cData['projectName']])) {
                    $context->setProject($projectMap[$cData['projectName']]);
                }
                foreach ($cData['tags'] ?? [] as $tagName) {
                    $context->addTag($this->tagService->findOrCreate($tagName));
                }
                $this->entityManager->persist($context);
                $result['created']++;
            }
        }

        // Import templates
        $existingTemplates = [];
        foreach ($this->templateRepository->findAllOrdered($user) as $t) {
            $existingTemplates[$t->getTitle()] = $t;
        }

        foreach ($data['templates'] ?? [] as $tData) {
            $title = $tData['title'] ?? '';
            if (empty($title)) continue;

            if (isset($existingTemplates[$title])) {
                if ($strategy === 'overwrite') {
                    $template = $existingTemplates[$title];
                    $template->setBody($tData['body'] ?? '');
                    $template->setTargetModel($tData['targetModel'] ?? PromptTemplate::MODEL_UNIVERSAL);
                    $template->setDescription($tData['description'] ?? null);
                    $template->clearTags();
                    foreach ($tData['tags'] ?? [] as $tagName) {
                        $template->addTag($this->tagService->findOrCreate($tagName));
                    }
                    $result['updated']++;
                } else {
                    $result['skipped']++;
                }
            } else {
                $template = new PromptTemplate();
                $template->setTitle($title);
                $template->setBody($tData['body'] ?? '');
                $template->setTargetModel($tData['targetModel'] ?? PromptTemplate::MODEL_UNIVERSAL);
                $template->setDescription($tData['description'] ?? null);
                $template->setOwner($user);
                foreach ($tData['tags'] ?? [] as $tagName) {
                    $template->addTag($this->tagService->findOrCreate($tagName));
                }
                $this->entityManager->persist($template);
                $result['created']++;
            }
        }

        // Import snippets
        $existingSnippets = [];
        foreach ($this->snippetRepository->findAllOrdered($user) as $s) {
            $existingSnippets[$s->getTitle()] = $s;
        }

        foreach ($data['snippets'] ?? [] as $sData) {
            $title = $sData['title'] ?? '';
            if (empty($title)) continue;

            if (isset($existingSnippets[$title])) {
                if ($strategy === 'overwrite') {
                    $snippet = $existingSnippets[$title];
                    $snippet->setContent($sData['content'] ?? '');
                    $result['updated']++;
                } else {
                    $result['skipped']++;
                }
            } else {
                $snippet = new Snippet();
                $snippet->setTitle($title);
                $snippet->setContent($sData['content'] ?? '');
                $snippet->setOwner($user);
                $this->entityManager->persist($snippet);
                $result['created']++;
            }
        }

        $this->entityManager->flush();

        return $result;
    }
}
