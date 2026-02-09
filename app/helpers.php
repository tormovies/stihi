<?php

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
