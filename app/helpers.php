<?php

if (! function_exists('markdown_to_html')) {
    /**
     * Конвертирует Markdown в HTML (для обратной совместимости со старыми записями в БД).
     */
    function markdown_to_html(string $markdown): string
    {
        if (trim($markdown) === '') {
            return '';
        }
        $env = \League\CommonMark\Environment\Environment::createCommonMarkEnvironment();
        $converter = new \League\CommonMark\MarkdownConverter($env);
        return trim($converter->convert($markdown)->getContent()) ?: '';
    }
}

if (! function_exists('e_decode')) {
    /**
     * Decode HTML entities to actual characters (e.g. &#8230; → …).
     */
    function e_decode(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
