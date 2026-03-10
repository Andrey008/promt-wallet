<?php

namespace App\Service;

use App\Entity\Context;
use App\Entity\Project;
use App\Entity\PromptTemplate;
use App\Entity\Snippet;

class PromptComposerService
{
    /**
     * Available placeholders for templates
     */
    public const PLACEHOLDERS = [
        '{{context}}' => 'All selected contexts combined',
        '{{snippets}}' => 'All selected snippets combined',
        '{{project.name}}' => 'Project name',
        '{{project.stack}}' => 'Project technology stack',
        '{{project.description}}' => 'Project description',
        '{{date}}' => 'Current date (Y-m-d)',
        '{{datetime}}' => 'Current date and time',
    ];

    /**
     * Compose final prompt from template and contexts
     *
     * @param Context[] $contexts
     * @param Snippet[] $snippets
     */
    public function compose(
        PromptTemplate $template,
        array $contexts,
        ?Project $project = null,
        array $snippets = []
    ): string {
        $body = $template->getBody();

        return $this->replacePlaceholders($body, $contexts, $project, $snippets);
    }

    /**
     * Render contexts as formatted text block
     *
     * @param Context[] $contexts
     */
    public function renderContexts(array $contexts): string
    {
        if (empty($contexts)) {
            return '';
        }

        $parts = [];
        foreach ($contexts as $context) {
            $parts[] = $this->renderSingleContext($context);
        }

        return implode("\n\n---\n\n", $parts);
    }

    /**
     * Render a single context
     */
    private function renderSingleContext(Context $context): string
    {
        $output = "## " . $context->getTitle() . "\n\n";
        $output .= $context->getContent();

        return $output;
    }

    /**
     * Render snippets as formatted text block
     *
     * @param Snippet[] $snippets
     */
    public function renderSnippets(array $snippets): string
    {
        if (empty($snippets)) {
            return '';
        }

        $parts = [];
        foreach ($snippets as $snippet) {
            $parts[] = "## " . $snippet->getTitle() . "\n\n" . $snippet->getContent();
        }

        return implode("\n\n---\n\n", $parts);
    }

    /**
     * Replace placeholders in template body
     *
     * @param Context[] $contexts
     * @param Snippet[] $snippets
     */
    public function replacePlaceholders(
        string $body,
        array $contexts,
        ?Project $project,
        array $snippets = []
    ): string {
        $replacements = [
            '{{context}}' => $this->renderContexts($contexts),
            '{{snippets}}' => $this->renderSnippets($snippets),
            '{{date}}' => date('Y-m-d'),
            '{{datetime}}' => date('Y-m-d H:i:s'),
        ];

        if ($project !== null) {
            $replacements['{{project.name}}'] = $project->getName();
            $replacements['{{project.stack}}'] = $project->getStackAsString();
            $replacements['{{project.description}}'] = $project->getDescription() ?? '';
        } else {
            $replacements['{{project.name}}'] = '';
            $replacements['{{project.stack}}'] = '';
            $replacements['{{project.description}}'] = '';
        }

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $body
        );
    }

    /**
     * Get available placeholders with descriptions
     *
     * @return array<string, string>
     */
    public function getAvailablePlaceholders(): array
    {
        return self::PLACEHOLDERS;
    }

    /**
     * Preview composed prompt with sample data
     */
    public function previewTemplate(PromptTemplate $template): string
    {
        $sampleContext = new Context();
        $sampleContext->setTitle('Sample Context');
        $sampleContext->setContent('This is sample context content that would be inserted here.');

        $sampleProject = new Project();
        $sampleProject->setName('Sample Project');
        $sampleProject->setStackFromString('PHP, Symfony, PostgreSQL');
        $sampleProject->setDescription('A sample project description');

        return $this->compose($template, [$sampleContext], $sampleProject);
    }

    /**
     * Extract used placeholders from template body
     *
     * @return string[]
     */
    public function extractPlaceholders(string $body): array
    {
        preg_match_all('/\{\{([^}]+)\}\}/', $body, $matches);

        return array_unique($matches[0] ?? []);
    }

    /**
     * Validate that all placeholders in template are valid
     *
     * @return string[] Invalid placeholder names
     */
    public function validatePlaceholders(string $body): array
    {
        $used = $this->extractPlaceholders($body);
        $valid = array_keys(self::PLACEHOLDERS);
        $invalid = [];

        foreach ($used as $placeholder) {
            if (!in_array($placeholder, $valid, true)) {
                $invalid[] = $placeholder;
            }
        }

        return $invalid;
    }
}
