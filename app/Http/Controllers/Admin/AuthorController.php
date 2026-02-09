<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HomeController;
use App\Models\Author;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->get('sort', 'name');
        $dir = strtolower($request->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSort = ['name', 'slug', 'sort_order', 'years_of_life'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'name';
        }

        $query = Author::query();
        if ($sort === 'years_of_life') {
            // Сортировка только по году смерти (второе число в формате «год–год»)
            $query->orderByRaw('(years_of_life IS NULL OR years_of_life = \'\')')
                ->orderByRaw(
                    "CAST(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(COALESCE(years_of_life,''), '–', -1), '-', -1)) AS UNSIGNED) " . $dir
                );
        } else {
            $query->orderBy($sort, $dir);
        }
        $authors = $query->paginate(30)->withQueryString();
        return view('admin.authors.index', compact('authors', 'sort', 'dir'));
    }

    public function create(): View
    {
        return view('admin.authors.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'slug' => 'required|string|max:255|unique:authors,slug',
            'name' => 'required|string|max:255',
            'years_of_life' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'h1' => 'nullable|string|max:255',
            'h1_description' => 'nullable|string|max:500',
        ]);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        Author::create($data);
        HomeController::clearCache();
        return redirect()->route('admin.authors.index')->with('success', 'Автор добавлен.');
    }

    public function edit(Author $author): View
    {
        return view('admin.authors.edit', compact('author'));
    }

    public function update(Request $request, Author $author): RedirectResponse
    {
        $data = $request->validate([
            'slug' => 'required|string|max:255|unique:authors,slug,' . $author->id,
            'name' => 'required|string|max:255',
            'years_of_life' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'h1' => 'nullable|string|max:255',
            'h1_description' => 'nullable|string|max:500',
        ]);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $author->update($data);
        HomeController::clearCache();
        return redirect()->route('admin.authors.index')->with('success', 'Автор обновлён.');
    }

    public function destroy(Author $author): RedirectResponse
    {
        $author->delete();
        HomeController::clearCache();
        return redirect()->route('admin.authors.index')->with('success', 'Автор удалён.');
    }
}
