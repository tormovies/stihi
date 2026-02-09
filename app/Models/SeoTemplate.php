<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoTemplate extends Model
{
    protected $fillable = ['type', 'meta_title', 'meta_description', 'h1', 'h1_description'];

    public static function getForType(string $type): ?self
    {
        return self::where('type', $type)->first();
    }

    /**
     * Render template with placeholders.
     * Poem: {title}, {author}
     * Author: {name}
     * Page: {title}
     */
    public static function renderTitle(string $type, $entity): string
    {
        $str = self::getTitleString($type, $entity);
        return self::replacePlaceholders($str, $type, $entity);
    }

    public static function renderDescription(string $type, $entity): string
    {
        $str = self::getDescriptionString($type, $entity);
        return self::replacePlaceholders($str ?? '', $type, $entity);
    }

    public static function renderH1(string $type, $entity): string
    {
        $str = self::getH1String($type, $entity);
        return self::replacePlaceholders($str ?? '', $type, $entity);
    }

    public static function renderH1Description(string $type, $entity): string
    {
        $str = self::getH1DescriptionString($type, $entity);
        return self::replacePlaceholders($str, $type, $entity);
    }

    /** Приоритет у полей сущности (автор, страница, стих), иначе глобальный шаблон. */
    protected static function getTitleString(string $type, $entity): string
    {
        if ($entity && trim((string) ($entity->meta_title ?? '')) !== '') {
            return (string) $entity->meta_title;
        }
        $tpl = self::getForType($type);
        return $tpl?->meta_title ?: self::defaultTitle($type, $entity);
    }

    protected static function getDescriptionString(string $type, $entity): string
    {
        if ($entity && trim((string) ($entity->meta_description ?? '')) !== '') {
            return (string) $entity->meta_description;
        }
        $tpl = self::getForType($type);
        return $tpl?->meta_description ?: self::defaultDescription($type, $entity);
    }

    protected static function getH1String(string $type, $entity): string
    {
        if ($entity && trim((string) ($entity->h1 ?? '')) !== '') {
            return (string) $entity->h1;
        }
        $tpl = self::getForType($type);
        return $tpl?->h1 ?: self::defaultH1($type, $entity);
    }

    protected static function getH1DescriptionString(string $type, $entity): string
    {
        if ($entity && trim((string) ($entity->h1_description ?? '')) !== '') {
            return (string) $entity->h1_description;
        }
        $tpl = self::getForType($type);
        return $tpl?->h1_description ?? '';
    }

    protected static function defaultH1(string $type, $entity): string
    {
        if ($type === 'home') {
            return 'Стихотворения поэтов классиков';
        }
        if ($type === 'poem' && $entity) {
            return e_decode($entity->title ?? '');
        }
        if ($type === 'author' && $entity) {
            return e_decode($entity->name ?? '');
        }
        if ($type === 'page' && $entity) {
            return e_decode($entity->title ?? '');
        }
        return '';
    }

    protected static function replacePlaceholders(string $str, string $type, $entity): string
    {
        $name = $title = $authorStr = $years = '';
        if ($entity) {
            $name = isset($entity->name) ? (string) $entity->name : '';
            $title = isset($entity->title) ? (string) $entity->title : $name;
            $author = $entity->author ?? null;
            if (is_object($author) && isset($author->name)) {
                $authorStr = (string) $author->name;
            } elseif (is_object($author)) {
                $authorStr = '';
            } else {
                $authorStr = (string) $author;
            }
            $years = isset($entity->years_of_life) ? (string) $entity->years_of_life : '';
        }
        return str_replace(
            ['{title}', '{name}', '{author}', '{years}'],
            [e_decode($title), e_decode($name), e_decode($authorStr), e_decode($years)],
            $str
        );
    }

    protected static function defaultTitle(string $type, $entity): string
    {
        if ($type === 'poem' && $entity) {
            return e_decode($entity->title) . ' — ' . e_decode($entity->author->name ?? '');
        }
        if ($type === 'author' && $entity) {
            return e_decode($entity->name) . ' — стихи';
        }
        if ($type === 'page' && $entity) {
            return e_decode($entity->title ?? '');
        }
        return 'Стихотворения';
    }

    protected static function defaultDescription(string $type, $entity): string
    {
        if ($type === 'poem' && $entity) {
            return 'Стихотворение ' . e_decode($entity->title) . ', ' . e_decode($entity->author->name ?? '') . '. Читать текст.';
        }
        if ($type === 'author' && $entity) {
            return 'Стихи ' . e_decode($entity->name) . '. Читать текст стихотворений.';
        }
        return 'Портал классической поэзии';
    }
}
