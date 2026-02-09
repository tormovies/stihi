<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Author;
use App\Models\Page;
use App\Models\Poem;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'authorsCount' => Author::count(),
            'poemsCount' => Poem::count(),
            'pagesCount' => Page::count(),
        ]);
    }
}
