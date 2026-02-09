<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SitemapController;
use App\Models\SeoPage;
use App\Models\SeoTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeoController extends Controller
{
    public function index(): View
    {
        $templates = SeoTemplate::orderBy('type')->get()->keyBy('type');
        $seoPages = SeoPage::orderBy('path')->paginate(20);
        $sitemapUpdatedAt = SitemapController::getLastUpdatedAt();

        return view('admin.seo.index', [
            'templates' => $templates,
            'seoPages' => $seoPages,
            'sitemapUpdatedAt' => $sitemapUpdatedAt,
        ]);
    }

    public function updateTemplates(Request $request): RedirectResponse
    {
        $types = ['home', 'page', 'author', 'poem'];
        foreach ($types as $type) {
            SeoTemplate::updateOrCreate(
                ['type' => $type],
                [
                    'meta_title' => $request->input("templates.{$type}.meta_title"),
                    'meta_description' => $request->input("templates.{$type}.meta_description"),
                    'h1' => $request->input("templates.{$type}.h1"),
                    'h1_description' => $request->input("templates.{$type}.h1_description"),
                ]
            );
        }
        HomeController::clearCache();
        return redirect()->route('admin.seo.index')->with('success', 'SEO-шаблоны сохранены.');
    }

    public function storeSeoPage(Request $request): RedirectResponse
    {
        $request->validate([
            'path' => 'required|string|max:255|regex:/^[a-z0-9\-]+$/|unique:seo_pages,path',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ], [
            'path.regex' => 'Только латинские буквы, цифры и дефис.',
            'path.unique' => 'Такой путь уже есть.',
        ]);

        SeoPage::create($request->only('path', 'meta_title', 'meta_description'));
        return redirect()->route('admin.seo.index')->with('success', 'SEO-страница добавлена.');
    }

    public function updateSeoPage(Request $request, SeoPage $seoPage): RedirectResponse
    {
        $request->validate([
            'path' => 'required|string|max:255|regex:/^[a-z0-9\-]+$/|unique:seo_pages,path,' . $seoPage->id,
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ], [
            'path.regex' => 'Только латинские буквы, цифры и дефис.',
            'path.unique' => 'Такой путь уже есть.',
        ]);

        $seoPage->update($request->only('path', 'meta_title', 'meta_description'));
        return redirect()->route('admin.seo.index')->with('success', 'SEO-страница сохранена.');
    }

    public function destroySeoPage(SeoPage $seoPage): RedirectResponse
    {
        $seoPage->delete();
        return redirect()->route('admin.seo.index')->with('success', 'SEO-страница удалена.');
    }

    public function sitemapRefresh(): RedirectResponse
    {
        SitemapController::regenerate();
        return redirect()->route('admin.seo.index')->with('success', 'Sitemap обновлён.');
    }
}
