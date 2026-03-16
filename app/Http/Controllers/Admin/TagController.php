<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TagController extends Controller
{
    public function index(Request $request): View
    {
        $q = $request->input('q', '');
        $sort = $request->input('sort', 'sort_order');
        $order = strtolower($request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (! in_array($sort, ['name', 'slug', 'sort_order', 'updated_at', 'poems_count'], true)) {
            $sort = 'sort_order';
        }

        $query = Tag::query()->withCount('poems');

        if ($q !== '') {
            $term = '%' . addcslashes($q, '%_\\') . '%';
            $query->where(function ($builder) use ($term) {
                $builder->where('name', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhere('h1', 'like', $term)
                    ->orWhere('meta_title', 'like', $term)
                    ->orWhere('meta_description', 'like', $term);
            });
        }

        $query->orderBy($sort, $order);
        $tags = $query->paginate(25)->withQueryString();

        return view('admin.tags.index', [
            'tags' => $tags,
            'q' => $q,
            'sort' => $sort,
            'order' => $order,
        ]);
    }

    public function create(): View
    {
        return view('admin.tags.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tags,slug',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'h1' => 'nullable|string|max:255',
            'h1_description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        Tag::create($data);
        return redirect()->route('admin.tags.index')->with('success', 'Тег добавлен.');
    }

    public function edit(Tag $tag): View
    {
        return view('admin.tags.edit', compact('tag'));
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tags,slug,' . $tag->id,
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'h1' => 'nullable|string|max:255',
            'h1_description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $tag->update($data);
        return redirect()->route('admin.tags.index')->with('success', 'Тег обновлён.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();
        return redirect()->route('admin.tags.index')->with('success', 'Тег удалён.');
    }

    public function bulkCreate(): View
    {
        return view('admin.tags.bulk');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $request->validate(['lines' => 'required|string']);
        $lines = array_filter(array_map('trim', explode("\n", $request->input('lines'))));
        $created = 0;
        $skipped = 0;

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            $name = $line;
            // Slug в нижнем регистре, чтобы дубли не зависели от регистра
            $slug = mb_strtolower(Str::slug($name));
            if ($slug === '') {
                $skipped++;
                continue;
            }
            // Дубли по slug (без учёта регистра)
            if (Tag::whereRaw('LOWER(slug) = ?', [mb_strtolower($slug)])->exists()) {
                $skipped++;
                continue;
            }
            // Дубли по названию (без учёта регистра): "Стихи про весну" и "стихи про весну" — один тег
            if (Tag::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
                $skipped++;
                continue;
            }
            Tag::create([
                'name' => $name,
                'slug' => $slug,
                'sort_order' => 0,
            ]);
            $created++;
        }

        $message = "Добавлено тегов: {$created}.";
        if ($skipped > 0) {
            $message .= " Пропущено (пустой slug или дубликат): {$skipped}.";
        }
        return redirect()->route('admin.tags.index')->with('success', $message);
    }
}
