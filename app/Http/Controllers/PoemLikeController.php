<?php

namespace App\Http\Controllers;

use App\Models\Poem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PoemLikeController extends Controller
{
    public const COOKIE_NAME = 'poem_likes';
    public const READ_COOKIE_NAME = 'poem_read';
    private const COOKIE_DAYS = 365;
    public const READ_COOKIE_MAX = 50;

    /**
     * Список id прочитанных стихов для отображения (страница автора, стиха).
     * Cookie хранит slug'и — по ним получаем id из БД.
     */
    public static function getReadIds(Request $request): array
    {
        $slugs = self::getReadSlugs($request);
        if ($slugs === []) {
            return [];
        }
        return Poem::whereIn('slug', $slugs)->whereNotNull('published_at')->pluck('id')->all();
    }

    /**
     * Список slug'ов прочитанных стихов из cookie (последние READ_COOKIE_MAX).
     */
    public static function getReadSlugs(Request $request): array
    {
        $raw = $request->cookie(self::READ_COOKIE_NAME);
        if ($raw === null || $raw === '') {
            return [];
        }
        $raw = trim($raw);
        $json = null;
        if (str_starts_with($raw, '[')) {
            $json = $raw;
        } elseif (preg_match('/^[A-Za-z0-9+\/=]+$/', $raw)) {
            $decoded = base64_decode($raw, true);
            if ($decoded !== false) {
                $json = $decoded;
            }
        }
        if ($json === null && str_contains($raw, '%')) {
            $json = rawurldecode($raw);
        }
        if ($json === null) {
            $json = $raw;
        }
        $dec = json_decode($json, true);
        if (!is_array($dec)) {
            return [];
        }
        $dec = array_values(array_filter($dec, 'is_string'));
        return array_slice($dec, -self::READ_COOKIE_MAX);
    }

    /**
     * Отметить стих как прочитанный. Cookie устанавливается с сервера (надёжнее, чем из JS).
     */
    public function markAsRead(Request $request, string $slug): JsonResponse
    {
        $poem = Poem::where('slug', $slug)->whereNotNull('published_at')->firstOrFail();
        $slugs = self::getReadSlugs($request);
        $slug = $poem->slug;
        $slugs = array_values(array_filter($slugs, fn ($s) => $s !== $slug));
        $slugs[] = $slug;
        $slugs = array_slice($slugs, -self::READ_COOKIE_MAX);
        $cookie = cookie(
            self::READ_COOKIE_NAME,
            json_encode($slugs),
            self::COOKIE_DAYS * 24 * 60,
            '/',
            null,
            false,
            false
        );
        return response()->json(['ok' => true])->cookie($cookie);
    }

    /**
     * Поставить лайк стиху. Один раз с одного браузера (по cookie).
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $poem = Poem::whereNotNull('published_at')->findOrFail($id);
        $liked = $request->cookie(self::COOKIE_NAME);
        $ids = $liked ? json_decode($liked, true) : [];
        if (!is_array($ids)) {
            $ids = [];
        }
        if (in_array($poem->id, $ids, true)) {
            return response()->json(['likes' => $poem->likes, 'already' => true]);
        }
        $poem->increment('likes');
        $ids[] = $poem->id;
        $cookie = cookie(self::COOKIE_NAME, json_encode($ids), self::COOKIE_DAYS * 24 * 60, '/', null, false, true);
        return response()->json(['likes' => $poem->fresh()->likes])->cookie($cookie);
    }
}
