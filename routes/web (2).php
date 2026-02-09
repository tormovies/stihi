<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\AuthorController as AdminAuthorController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PoemController as AdminPoemController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SlugController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::middleware('guest')->group(function () {
    Route::get('admin/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('admin/login', [AdminAuthController::class, 'login']);
});

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('authors', AdminAuthorController::class)->except('show');
    Route::resource('poems', AdminPoemController::class)->except('show');
    Route::resource('pages', AdminPageController::class)->except('show');
});

Route::get('/{slug}', [SlugController::class, 'show'])->where('slug', '[a-z0-9\-]+')->name('slug');
