<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Poem;
use App\Models\PoemSunoAnalysis;
use App\Services\PoemSunoDeepSeekService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SunoController extends Controller
{
    public function index(Request $request): View
    {
        $analysisFilter = $request->get('analysis', 'with'); // with|without|all
        if (!in_array($analysisFilter, ['with', 'without', 'all'], true)) {
            $analysisFilter = 'with';
        }

        $query = Poem::query()
            ->with(['author:id,name,slug', 'sunoAnalysis'])
            ->whereNotNull('published_at')
            ->whereBetween('body_length', [400, 2000]);

        if ($analysisFilter === 'with') {
            $query->whereHas('sunoAnalysis');
        } elseif ($analysisFilter === 'without') {
            $query->whereDoesntHave('sunoAnalysis');
        }

        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('slug', 'like', $term)
                    ->orWhereHas('author', fn ($a) => $a->where('name', 'like', $term))
                    ->orWhereHas('sunoAnalysis', function ($a) use ($term) {
                        $a->where('styles', 'like', $term)
                            ->orWhere('best_overall', 'like', $term)
                            ->orWhere('best_viral', 'like', $term)
                            ->orWhere('best_cult', 'like', $term);
                    });
            });
        }
        if ($request->filled('author_id')) {
            $query->where('author_id', $request->author_id);
        }
        if ($request->filled('song_status')) {
            $allowedSong = array_keys(Poem::songStatusOptions());
            if (in_array($request->song_status, $allowedSong, true)) {
                $query->where('song_status', $request->song_status);
            }
        }
        if ($request->filled('status')) {
            $query->whereHas('sunoAnalysis', fn ($q) => $q->where('status', $request->status));
        }
        if ($request->filled('male')) {
            $query->whereHas('sunoAnalysis', fn ($q) => $q->where('male_verdict', $request->male));
        }
        if ($request->filled('folk')) {
            $query->whereHas('sunoAnalysis', fn ($q) => $q->where('folk_verdict', $request->folk));
        }
        if ($request->filled('comfort')) {
            $query->whereHas('sunoAnalysis', fn ($q) => $q->where('comfort_verdict', $request->comfort));
        }
        if ($request->filled('suitable')) {
            $yes = $request->suitable === '1' || $request->suitable === 'yes';
            $query->whereHas('sunoAnalysis', fn ($q) => $q->where('suitable_for_suno', $yes));
        }

        $sort = $request->get('sort', 'updated_at');
        $order = strtolower($request->get('order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortable = ['score_total', 'status', 'folk_fit', 'comfort_fit', 'updated_at'];
        if (!in_array($sort, $sortable, true)) {
            $sort = 'updated_at';
        }

        if ($analysisFilter === 'with' || $analysisFilter === 'all') {
            $query->leftJoin('poem_suno_analyses', 'poems.id', '=', 'poem_suno_analyses.poem_id')
                ->select('poems.*');

            if ($sort === 'status') {
                $query->orderByRaw(
                    "CASE poem_suno_analyses.status
                        WHEN 'super' THEN 4
                        WHEN 'strong' THEN 3
                        WHEN 'medium' THEN 2
                        WHEN 'weak' THEN 1
                        ELSE 0 END " . $order
                );
            } else {
                $column = $sort === 'updated_at' ? 'poem_suno_analyses.updated_at' : 'poem_suno_analyses.' . $sort;
                $query->orderBy($column, $order);
            }
            $query->orderBy('poems.id');
        } else {
            $query->orderBy('poems.id', $order);
        }

        $poems = $query->paginate(30)->withQueryString();
        $authors = Author::orderBy('name')->get(['id', 'name']);

        return view('admin.suno.index', [
            'poems' => $poems,
            'authors' => $authors,
            'analysisFilter' => $analysisFilter,
            'sort' => $sort,
            'order' => $order,
            'statusOptions' => PoemSunoAnalysis::statusOptions(),
            'verdictOptions' => PoemSunoAnalysis::verdictOptions(),
        ]);
    }

    public function show(Poem $poem): JsonResponse
    {
        $poem->load(['author:id,name,slug', 'sunoAnalysis']);
        $a = $poem->sunoAnalysis;
        if (!$a) {
            return response()->json(['error' => 'Нет Suno-анализа'], 404);
        }

        return response()->json([
            'poem' => [
                'id' => $poem->id,
                'title' => $poem->title,
                'slug' => $poem->slug,
                'url' => url('/' . $poem->slug),
                'author' => $poem->author?->name,
                'body_length' => $poem->body_length,
            ],
            'analysis' => [
                'status' => $a->status,
                'status_label' => $a->statusLabel(),
                'suitable_for_suno' => $a->suitable_for_suno,
                'scores' => [
                    'hook' => $a->score_hook,
                    'rhythm' => $a->score_rhythm,
                    'dynamics' => $a->score_dynamics,
                    'plot' => $a->score_plot,
                    'vocal_air' => $a->score_vocal_air,
                    'total' => $a->score_total,
                ],
                'male' => ['fit' => $a->male_fit, 'verdict' => $a->male_verdict, 'why' => $a->male_why],
                'folk' => ['fit' => $a->folk_fit, 'verdict' => $a->folk_verdict, 'why' => $a->folk_why],
                'comfort' => ['fit' => $a->comfort_fit, 'verdict' => $a->comfort_verdict, 'why' => $a->comfort_why],
                'marked_lyrics' => $a->marked_lyrics,
                'styles' => $a->styles,
                'best_overall' => $a->best_overall,
                'best_viral' => $a->best_viral,
                'best_cult' => $a->best_cult,
                'structure_notes' => $a->structure_notes,
                'risks' => $a->risks,
                'updated_at' => $a->updated_at?->format('d.m.Y H:i'),
            ],
        ]);
    }

    public function reanalyze(Poem $poem, PoemSunoDeepSeekService $service): RedirectResponse
    {
        set_time_limit(600);
        $result = $service->runBatch($poem->id);

        if ($result['error']) {
            return back()->with('success', null)->withErrors(['suno' => $result['error']]);
        }

        return back()->with('success', $result['message'] ?? 'Suno-анализ обновлён.');
    }

    public function destroy(PoemSunoAnalysis $suno): RedirectResponse
    {
        $suno->delete();

        return back()->with('success', 'Suno-анализ удалён. Стих снова в очереди.');
    }
}
