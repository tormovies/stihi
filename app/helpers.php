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

if (! function_exists('public_site_url')) {
    /**
     * Абсолютный URL (для sitemap.xml и мест, где нужен полный URI). В шаблонах ссылок внутри сайта предпочтительны относительные пути.
     *
     * @param  string  $path  Путь без ведущего слэша, например "ahmatova" или "stih/analiz"
     */
    function public_site_url(string $path = ''): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $path = trim($path, '/');
        if ($path === '') {
            return $base;
        }

        return $base . '/' . $path;
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

if (! function_exists('normalize_poem_title')) {
    /**
     * В названии стиха: entity/символ многоточия → три точки ASCII (...).
     */
    function normalize_poem_title(string $title): string
    {
        $title = str_replace(
            ['&#8230;', '&#x2026;', '&#X2026;', '&hellip;'],
            '...',
            $title
        );

        return str_replace('…', '...', $title);
    }
}

if (! function_exists('poem_body_plain_export')) {
    /**
     * Текст стиха для экспорта: без HTML, с переносами строк.
     */
    function poem_body_plain_export(?string $body): string
    {
        if ($body === null || trim($body) === '') {
            return '';
        }

        $text = e_decode($body);
        $text = preg_replace("/\r\n|\r/u", "\n", $text);

        if (preg_match('/<[a-zA-Z][^>]*>/', $text)) {
            // В импорте WordPress строка обычно: «текст<br />\n» — перенос уже есть, br лишний.
            $text = preg_replace('/<br\s*\/?>/ui', '', $text);
            $text = preg_replace('/<\/p>\s*<p[^>]*>/ui', "\n", $text);
            $text = preg_replace('/<\/?p[^>]*>/ui', '', $text);
            $text = strip_tags($text);
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $lines = explode("\n", $text);
        $lines = array_map(static fn ($line) => rtrim($line), $lines);
        $text = implode("\n", $lines);
        $text = preg_replace("/\n{3,}/u", "\n\n", $text);

        return trim($text);
    }
}
