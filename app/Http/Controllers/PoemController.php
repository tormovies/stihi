<?php

namespace App\Http\Controllers;

use App\Models\Poem;
use Illuminate\View\View;

class PoemController extends Controller
{
    public function show(string $slug): View
    {
        $poem = Poem::where('slug', $slug)->whereNotNull('published_at')->firstOrFail();
        return view('poem', ['poem' => $poem]);
    }
}
