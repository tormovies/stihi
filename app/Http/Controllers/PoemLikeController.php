<?php

namespace App\Http\Controllers;

use App\Models\Poem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PoemLikeController extends Controller
{
    public const COOKIE_NAME = 'poem_likes';
    private const COOKIE_DAYS = 365;

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
