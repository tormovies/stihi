<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PoemAnalysis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PoemAnalysisController extends Controller
{
    public function index(Request $request): View
    {
        $q = $request->input('q', '');
        $sort = $request->input('sort', 'updated_at');
        $order = strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! in_array($sort, ['updated_at', 'author', 'poem'], true)) {
            $sort = 'updated_at';
        }

        $query = PoemAnalysis::with('poem.author');

        if ($q !== '') {
            $term = '%' . addcslashes($q, '%_\\') . '%';
            $query->where(function ($builder) use ($term) {
                $builder->where('poem_analyses.h1', 'like', $term)
                    ->orWhere('poem_analyses.meta_title', 'like', $term)
                    ->orWhere('poem_analyses.meta_description', 'like', $term)
                    ->orWhereHas('poem', function ($poemQuery) use ($term) {
                        $poemQuery->where('poems.title', 'like', $term)
                            ->orWhereHas('author', function ($authorQuery) use ($term) {
                                $authorQuery->where('authors.name', 'like', $term);
                            });
                    });
            });
        }

        if ($sort === 'author') {
            $query->join('poems', 'poem_analyses.poem_id', '=', 'poems.id')
                ->join('authors', 'poems.author_id', '=', 'authors.id')
                ->select('poem_analyses.*')
                ->orderBy('authors.name', $order);
        } elseif ($sort === 'poem') {
            $query->join('poems', 'poem_analyses.poem_id', '=', 'poems.id')
                ->select('poem_analyses.*')
                ->orderBy('poems.title', $order);
        } else {
            $query->orderBy('poem_analyses.updated_at', $order);
        }

        $analyses = $query->paginate(20)->withQueryString();

        return view('admin.poem-analyses.index', [
            'analyses' => $analyses,
            'q' => $q,
            'sort' => $sort,
            'order' => $order,
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        if (! is_array($ids)) {
            $ids = $ids ? [(int) $ids] : [];
        }
        $ids = array_filter(array_map('intval', $ids));
        $deleted = $ids ? PoemAnalysis::whereIn('id', $ids)->delete() : 0;

        return redirect()
            ->route('admin.poem-analyses.index')
            ->with('message', $deleted ? "Удалено записей: {$deleted}." : 'Ничего не выбрано.');
    }
}
