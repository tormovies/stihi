<?php

namespace App\Http\Controllers;

use App\Models\Poem;
use Illuminate\View\View;

class PoemAnalysisController extends Controller
{
    public function show(string $slug): View
    {
        $poem = Poem::with(['author', 'analysis'])
            ->where('slug', $slug)
            ->whereNotNull('published_at')
            ->firstOrFail();

        $analysis = $poem->analysis;
        if (!$analysis) {
            abort(404);
        }

        return view('poem-analysis', [
            'poem' => $poem,
            'analysis' => $analysis,
        ]);
    }
}
