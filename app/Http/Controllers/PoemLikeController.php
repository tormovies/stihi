<?php

namespace App\Http\Controllers;

use App\Models\Poem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PoemLikeController extends Controller
{
    public const COOKIE_NAME = 'poem_likes';
    public const READ_COOKIE_NAME = 'poem_read';
    private const COOKIE_DAYS = 365;
    public const READ_COOKIE_MAX = 300;
    public const LIKES_COOKIE_MAX = 300;

    /**
     * Список id прочитанных стихов из cookie (последние READ_COOKIE_MAX).
     * В cookie хранятся id, как и в «Понравившееся».
     */
    public static function getReadIds(Request $request): array
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
        $dec = array_values(array_filter(array_map('intval', $dec), fn ($id) => $id > 0));
        return array_slice($dec, -self::READ_COOKIE_MAX);
    }

    /**
     * Отметить стих как прочитанный (храним id). Cookie устанавливается с сервера.
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $poem = Poem::whereNotNull('published_at')->findOrFail($id);
        $ids = self::getReadIds($request);
        $ids = array_values(array_filter($ids, fn ($i) => $i !== $poem->id));
        $ids[] = $poem->id;
        $ids = array_slice($ids, -self::READ_COOKIE_MAX);
        $cookie = cookie(
            self::READ_COOKIE_NAME,
            json_encode($ids),
            self::COOKIE_DAYS * 24 * 60,
            '/',
            null,
            false,
            false
        );
        return response()->json(['ok' => true])->cookie($cookie);
    }

    /**
     * Страница «Понравившиеся»: стихи из cookie poem_likes.
     */
    public function favorites(Request $request): View
    {
        $raw = $request->cookie(self::COOKIE_NAME);
        $ids = $raw ? json_decode($raw, true) : [];
        if (!is_array($ids)) {
            $ids = [];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $ids = array_slice($ids, -self::LIKES_COOKIE_MAX);
        $poems = collect();
        if ($ids !== []) {
            $byId = Poem::with('author')->whereIn('id', $ids)->whereNotNull('published_at')->get()->keyBy('id');
            foreach ($ids as $id) {
                if ($byId->has($id)) {
                    $poems->push($byId->get($id));
                }
            }
        }
        $authorOrder = $poems->pluck('author_id')->unique()->filter()->values();
        $poemsByAuthor = $authorOrder->map(function ($authorId) use ($poems) {
            $authorPoems = $poems->where('author_id', $authorId);
            $author = $authorPoems->first()->author ?? null;
            return [
                'author' => $author,
                'poems' => $authorPoems->sortBy('title')->values(),
            ];
        })->filter(fn ($g) => $g['author'] !== null)->values();

        return view('favorites', [
            'poems' => $poems,
            'poemsByAuthor' => $poemsByAuthor,
        ]);
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
        $ids = array_slice($ids, -self::LIKES_COOKIE_MAX);
        $cookie = cookie(self::COOKIE_NAME, json_encode($ids), self::COOKIE_DAYS * 24 * 60, '/', null, false, true);
        return response()->json(['likes' => $poem->fresh()->likes])->cookie($cookie);
    }

    /**
     * Убрать лайк: удалить из cookie и уменьшить счётчик в БД.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $poem = Poem::whereNotNull('published_at')->findOrFail($id);
        $raw = $request->cookie(self::COOKIE_NAME);
        $ids = $raw ? json_decode($raw, true) : [];
        if (!is_array($ids)) {
            $ids = [];
        }
        $ids = array_values(array_filter($ids, fn ($i) => (int) $i !== $poem->id));
        $ids = array_slice($ids, -self::LIKES_COOKIE_MAX);
        if ($poem->likes > 0) {
            $poem->decrement('likes');
        }
        $cookie = cookie(self::COOKIE_NAME, json_encode($ids), self::COOKIE_DAYS * 24 * 60, '/', null, false, true);
        return response()->json(['likes' => $poem->fresh()->likes])->cookie($cookie);
    }
}
