<?php

namespace App\Http\Controllers;

use App\Models\Poem;
use Illuminate\View\View;

class SongSelectedSecretController extends Controller
{
    /** Статический токен в URL (без .env). Сменить при утечке — обновить ссылку в админке. */
    public const ACCESS_TOKEN = 'a8f3e91c2d7b4056e1f8c4a9b6d2e0f7';

    /**
     * Секретная страница: стихи со статусом «Песня» = выбран.
     * URL: /up/song-selected/{ACCESS_TOKEN}
     */
    public function __invoke(string $token): View
    {
        if (!hash_equals(self::ACCESS_TOKEN, $token)) {
            abort(404);
        }

        $poems = Poem::query()
            ->where('song_status', Poem::SONG_STATUS_SELECTED)
            ->whereNotNull('published_at')
            ->with('author')
            ->orderBy('title')
            ->get();

        return view('song-selected-secret', [
            'poems' => $poems,
        ]);
    }
}
