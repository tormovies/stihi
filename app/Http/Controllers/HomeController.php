<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Page;
use App\Models\SeoTemplate;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    private const CACHE_KEY = 'home_view';
    private const CACHE_TTL_SECONDS = 600; // 10 минут

    public function index(): View
    {
        $data = Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            $homePage = Page::where('is_home', true)->where('is_published', true)->first();
            $seoHome = SeoTemplate::getForType('home');
            $authors = Author::orderBy('sort_order')->orderBy('name')->get();
            return [
                'page' => $homePage,
                'authors' => $authors,
                'seoHome' => $seoHome,
            ];
        });

        return view('home', $data);
    }

    /** Сброс кэша главной (вызвать при изменении страницы/авторов/SEO). */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
