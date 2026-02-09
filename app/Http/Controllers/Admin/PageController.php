<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HomeController;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View
    {
        $pages = Page::orderBy('title')->paginate(20);
        return view('admin.pages.index', compact('pages'));
    }

    public function create(): View
    {
        return view('admin.pages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'slug' => 'required|string|max:255|unique:pages,slug',
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'h1' => 'nullable|string|max:255',
            'h1_description' => 'nullable|string|max:500',
            'is_published' => 'boolean',
            'is_home' => 'boolean',
        ]);
        $data['is_published'] = $request->boolean('is_published');
        $data['is_home'] = $request->boolean('is_home');
        Page::create($data);
        HomeController::clearCache();
        return redirect()->route('admin.pages.index')->with('success', 'Страница добавлена.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $data = $request->validate([
            'slug' => 'required|string|max:255|unique:pages,slug,' . $page->id,
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'h1' => 'nullable|string|max:255',
            'h1_description' => 'nullable|string|max:500',
            'is_published' => 'boolean',
            'is_home' => 'boolean',
        ]);
        $data['is_published'] = $request->boolean('is_published');
        $data['is_home'] = $request->boolean('is_home');
        $page->update($data);
        HomeController::clearCache();
        return redirect()->route('admin.pages.index')->with('success', 'Страница обновлена.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();
        HomeController::clearCache();
        return redirect()->route('admin.pages.index')->with('success', 'Страница удалена.');
    }
}
