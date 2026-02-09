<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Page;
use App\Models\Poem;
use App\Models\SeoPage;
use App\Support\SlugNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SlugController extends Controller
{
    /**
     * Resolve /{slug} to Page, Author, Poem или редирект со старых URL.
     * Один маршрут без ограничения по slug, чтобы оба варианта (валидный и «битый») обрабатывались.
     */
    public function show(Request $request, string $slug): View|RedirectResponse
    {
        // Старые URL с _, %e2%84%96 и т.д. — редирект 301 на нормализованный URL
        if (!SlugNormalizer::isValid($slug)) {
            $normalized = SlugNormalizer::normalize($slug);
            $poem = Poem::where('slug', $normalized)->whereNotNull('published_at')->first();
            if (!$poem) {
                foreach (SlugNormalizer::variantsForLookup($slug) as $variant) {
                    $poem = Poem::where('slug', $variant)->whereNotNull('published_at')->first();
                    if ($poem) {
                        break;
                    }
                }
            }
            if ($poem) {
                $targetSlug = SlugNormalizer::isValid($poem->slug) ? $poem->slug : $normalized;
                return redirect()->to('/' . $targetSlug, 301);
            }
            abort(404);
        }

        if (strtolower($slug) === 'sample-post') {
            return redirect()->route('home', [], 301);
        }

        $page = Page::where('slug', $slug)->where('is_published', true)->first();
        if ($page) {
            if ($page->is_home) {
                return redirect()->route('home', [], 301);
            }
            return view('page', ['page' => $page]);
        }

        $author = Author::where('slug', $slug)->first();
        if ($author) {
            $poems = $author->publishedPoems()->orderBy('title')->paginate(50);
            return view('author', ['author' => $author, 'poems' => $poems]);
        }

        $poem = Poem::with('author')->where('slug', $slug)->whereNotNull('published_at')->first();
        if ($poem) {
            $likedIds = [];
            $raw = $request->cookie(PoemLikeController::COOKIE_NAME);
            if ($raw) {
                $dec = json_decode($raw, true);
                if (is_array($dec)) {
                    $likedIds = $dec;
                }
            }
            return view('poem', [
                'poem' => $poem,
                'liked' => in_array($poem->id, $likedIds, true),
            ]);
        }

        $seoPage = SeoPage::findByPath($slug);
        if ($seoPage) {
            return view('seo-page', ['seoPage' => $seoPage]);
        }

        abort(404);
    }
}
