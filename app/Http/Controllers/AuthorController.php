<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthorController extends Controller
{
    public function show(string $slug): View
    {
        $author = Author::where('slug', $slug)->firstOrFail();
        $poems = $author->publishedPoems()->orderBy('title')->paginate(50);
        return view('author', ['author' => $author, 'poems' => $poems]);
    }
}
