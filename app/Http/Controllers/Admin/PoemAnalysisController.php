<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PoemAnalysis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PoemAnalysisController extends Controller
{
    public function index(): View
    {
        $analyses = PoemAnalysis::with('poem.author')
            ->orderByDesc('poem_analyses.updated_at')
            ->paginate(20);

        return view('admin.poem-analyses.index', compact('analyses'));
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
