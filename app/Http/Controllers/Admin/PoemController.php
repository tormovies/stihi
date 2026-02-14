<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Poem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PoemController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->get('sort', 'updated_at');
        $order = strtolower($request->get('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['title', 'updated_at', 'likes', 'body_length'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'updated_at';
        }

        $query = Poem::with('author')->orderBy($sort, $order);
        if ($request->filled('author_id')) {
            $query->where('author_id', $request->author_id);
        }
        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhere('body', 'like', $term);
            });
        }
        if ($request->filled('length_from') && is_numeric($request->length_from)) {
            $query->where('body_length', '>=', (int) $request->length_from);
        }
        if ($request->filled('length_to') && is_numeric($request->length_to)) {
            $query->where('body_length', '<=', (int) $request->length_to);
        }
        $poems = $query->paginate(20)->withQueryString();
        $authors = Author::orderBy('name')->get();
        return view('admin.poems.index', compact('poems', 'authors', 'sort', 'order'));
    }

    public function create(): View
    {
        $authors = Author::orderBy('name')->get();
        return view('admin.poems.create', compact('authors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'author_id' => 'required|exists:authors,id',
            'slug' => 'required|string|max:255|unique:poems,slug',
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'h1' => 'nullable|string|max:255',
            'h1_description' => 'nullable|string|max:500',
            'published_at' => 'nullable|date',
        ]);
        $data['published_at'] = $data['published_at'] ?? now();
        Poem::create($data);
        return redirect()->route('admin.poems.index')->with('success', 'Стих добавлен.');
    }

    public function edit(Poem $poem): View
    {
        $authors = Author::orderBy('name')->get();
        return view('admin.poems.edit', compact('poem', 'authors'));
    }

    public function update(Request $request, Poem $poem): RedirectResponse
    {
        $data = $request->validate([
            'author_id' => 'required|exists:authors,id',
            'slug' => 'required|string|max:255|unique:poems,slug,' . $poem->id,
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'h1' => 'nullable|string|max:255',
            'h1_description' => 'nullable|string|max:500',
            'published_at' => 'nullable|date',
        ]);
        $poem->update($data);
        return redirect()->route('admin.poems.index')->with('success', 'Стих обновлён.');
    }

    public function destroy(Poem $poem): RedirectResponse
    {
        $poem->delete();
        return redirect()->route('admin.poems.index')->with('success', 'Стих удалён.');
    }
}
