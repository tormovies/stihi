<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\AuthorController as AdminAuthorController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PoemController as AdminPoemController;
use App\Http\Controllers\Admin\SecurityController as AdminSecurityController;
use App\Http\Controllers\Admin\SeoController as AdminSeoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PoemLikeController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SlugController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');
Route::post('/poem/{id}/like', [PoemLikeController::class, 'store'])->name('poem.like')->where('id', '[0-9]+');
Route::post('/poem/{id}/unlike', [PoemLikeController::class, 'destroy'])->name('poem.unlike')->where('id', '[0-9]+');
Route::post('/poem/read/{id}', [PoemLikeController::class, 'markAsRead'])->name('poem.read')->where('id', '[0-9]+');
Route::get('/favorites', [PoemLikeController::class, 'favorites'])->name('favorites');
Route::get('/robots.txt', function () {
    $sitemap = rtrim(config('app.url'), '/') . '/sitemap.xml';
    return response("User-agent: *\nDisallow:\n\nSitemap: {$sitemap}\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('robots');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap-debug', [SitemapController::class, 'debug'])->name('sitemap.debug');

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
    Route::get('security', [AdminSecurityController::class, 'index'])->name('security.index');
    Route::post('security', [AdminSecurityController::class, 'index'])->name('security.update');
    Route::get('seo', [AdminSeoController::class, 'index'])->name('seo.index');
    Route::post('seo/templates', [AdminSeoController::class, 'updateTemplates'])->name('seo.templates.update');
    Route::post('seo/pages', [AdminSeoController::class, 'storeSeoPage'])->name('seo.pages.store');
    Route::put('seo/pages/{seoPage}', [AdminSeoController::class, 'updateSeoPage'])->name('seo.pages.update');
    Route::delete('seo/pages/{seoPage}', [AdminSeoController::class, 'destroySeoPage'])->name('seo.pages.destroy');
    Route::post('seo/sitemap-refresh', [AdminSeoController::class, 'sitemapRefresh'])->name('seo.sitemap.refresh');
});

Route::get('/{slug}', [SlugController::class, 'show'])->name('slug');
