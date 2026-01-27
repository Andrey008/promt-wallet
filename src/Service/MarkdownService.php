<?php

namespace App\Service;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

class MarkdownService
{
    private MarkdownConverter $converter;

    public function __construct()
    {
        $config = [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ];

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());

        $this->converter = new MarkdownConverter($environment);
    }

    /**
     * Convert Markdown to HTML for preview
     */
    public function toHtml(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }

    /**
     * Strip Markdown formatting to plain text
     */
    public function toPlainText(string $markdown): string
    {
        // Remove code blocks
        $text = preg_replace('/```[\s\S]*?```/', '', $markdown);
        // Remove inline code
        $text = preg_replace('/`[^`]+`/', '', $text);
        // Remove headers
        $text = preg_replace('/^#+\s+/m', '', $text);
        // Remove bold/italic
        $text = preg_replace('/\*\*([^*]+)\*\*/', '$1', $text);
        $text = preg_replace('/\*([^*]+)\*/', '$1', $text);
        $text = preg_replace('/__([^_]+)__/', '$1', $text);
        $text = preg_replace('/_([^_]+)_/', '$1', $text);
        // Remove links, keep text
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);
        // Remove images
        $text = preg_replace('/!\[([^\]]*)\]\([^)]+\)/', '', $text);
        // Remove horizontal rules
        $text = preg_replace('/^[-*_]{3,}$/m', '', $text);
        // Remove list markers
        $text = preg_replace('/^[\s]*[-*+]\s+/m', '', $text);
        $text = preg_replace('/^[\s]*\d+\.\s+/m', '', $text);
        // Remove blockquotes
        $text = preg_replace('/^>\s*/m', '', $text);
        // Collapse multiple newlines
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Get excerpt from markdown content
     */
    public function getExcerpt(string $markdown, int $maxLength = 150): string
    {
        $plainText = $this->toPlainText($markdown);

        if (strlen($plainText) <= $maxLength) {
            return $plainText;
        }

        $excerpt = substr($plainText, 0, $maxLength);
        $lastSpace = strrpos($excerpt, ' ');

        if ($lastSpace !== false) {
            $excerpt = substr($excerpt, 0, $lastSpace);
        }

        return $excerpt . '...';
    }
}
