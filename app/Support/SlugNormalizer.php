<?php

namespace App\Support;

class SlugNormalizer
{
    /**
     * Нормализация slug до [a-z0-9\-]+ для маршрута.
     * Подчёркивание → дефис, № (и %e2%84%96) → -no-.
     */
    public static function normalize(string $slug): string
    {
        $s = $slug;
        $s = str_replace('_', '-', $s);
        $s = str_replace('%e2%84%96', '-no-', $s);
        $s = str_replace('№', '-no-', $s);
        $s = preg_replace('/[^a-z0-9\-]/u', '-', $s);
        $s = preg_replace('/-+/', '-', $s);
        $s = trim($s, '-');
        return $s === '' ? 'slug' : $s;
    }

    public static function isValid(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9\-]+$/', $slug);
    }

    /**
     * Варианты slug для поиска в БД: как в запросе, декодированный, с № вместо %e2%84%96 и наоборот.
     */
    public static function variantsForLookup(string $slug): array
    {
        $decoded = rawurldecode($slug);
        $withNo = str_replace('%e2%84%96', '№', $slug);
        $withPercent = str_replace('№', '%e2%84%96', $slug);
        return array_unique([$slug, $decoded, $withNo, $withPercent]);
    }
}
