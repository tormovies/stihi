<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\AuthorController as AdminAuthorController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PoemController as AdminPoemController;
use App\Http\Controllers\Admin\SecurityController as AdminSecurityController;
use App\Http\Controllers\Admin\SecurityGoneController as AdminSecurityGoneController;
use App\Http\Controllers\Admin\DeepSeekController as AdminDeepSeekController;
use App\Http\Controllers\Admin\PoemAnalysisController as AdminPoemAnalysisController;
use App\Http\Controllers\Admin\TagController as AdminTagController;
use App\Http\Controllers\Admin\SeoController as AdminSeoController;
use App\Http\Controllers\Admin\UrlRedirectController as AdminUrlRedirectController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PoemLikeController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\PoemAnalysisController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SlugController;
use App\Http\Controllers\SongSelectedSecretController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'index'])->name('search.index');
Route::get('/search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');
Route::post('/poem/{id}/like', [PoemLikeController::class, 'store'])->name('poem.like')->where('id', '[0-9]+');
Route::post('/poem/{id}/unlike', [PoemLikeController::class, 'destroy'])->name('poem.unlike')->where('id', '[0-9]+');
Route::post('/poem/read/{id}', [PoemLikeController::class, 'markAsRead'])->name('poem.read')->where('id', '[0-9]+');
Route::get('/favorites', [PoemLikeController::class, 'favorites'])->name('favorites');
Route::get('/ponravivshiesya-vsem', [PoemLikeController::class, 'likedByAll'])->name('liked.by.all');
Route::get('/tegi', [TagController::class, 'index'])->name('tags.index');
Route::get('/tegi/{slug}', [TagController::class, 'show'])->name('tags.show')->where('slug', '[a-z0-9\-]+');
Route::get('/robots.txt', function () {
    $sitemap = rtrim(config('app.url'), '/') . '/sitemap.xml';
    return response("User-agent: *\nDisallow:\n\nSitemap: {$sitemap}\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('robots');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap-debug', [SitemapController::class, 'debug'])->name('sitemap.debug');
Route::get('/up/song-selected/{token}', SongSelectedSecretController::class)
    ->where('token', '[a-f0-9]{32}')
    ->name('song-selected.secret');

Route::middleware('guest')->group(function () {
    Route::get('admin/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('admin/login', [AdminAuthController::class, 'login']);
});

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('authors', AdminAuthorController::class)->except('show');
    Route::patch('poems/{poem}/song-status', [AdminPoemController::class, 'updateSongStatus'])->name('poems.song-status');
    Route::resource('poems', AdminPoemController::class)->except('show');
    Route::get('poem-analyses', [AdminPoemAnalysisController::class, 'index'])->name('poem-analyses.index');
    Route::post('poem-analyses/destroy', [AdminPoemAnalysisController::class, 'destroy'])->name('poem-analyses.destroy');
    Route::resource('tags', AdminTagController::class)->except('show');
    Route::get('tags-bulk', [AdminTagController::class, 'bulkCreate'])->name('tags.bulk');
    Route::post('tags-bulk', [AdminTagController::class, 'bulkStore'])->name('tags.bulk.store');
    Route::resource('pages', AdminPageController::class)->except('show');
    Route::get('security', [AdminSecurityController::class, 'index'])->name('security.index');
    Route::post('security', [AdminSecurityController::class, 'index'])->name('security.update');
    Route::get('security/gone', [AdminSecurityGoneController::class, 'index'])->name('security.gone');
    Route::post('security/gone', [AdminSecurityGoneController::class, 'index']);
    Route::delete('security/gone/{id}', [AdminSecurityGoneController::class, 'destroy'])->name('security.gone.destroy');
    Route::delete('security/gone/exclude/{id}', [AdminSecurityGoneController::class, 'destroyExclude'])->name('security.gone.exclude.destroy');
    Route::get('seo', [AdminSeoController::class, 'index'])->name('seo.index');
    Route::get('deepseek', [AdminDeepSeekController::class, 'index'])->name('deepseek.index');
    Route::post('deepseek/settings', [AdminDeepSeekController::class, 'store'])->name('deepseek.settings.store');
    Route::post('deepseek/wipe/poems', [AdminDeepSeekController::class, 'wipePoems'])->name('deepseek.wipe.poems');
    Route::post('deepseek/wipe/authors', [AdminDeepSeekController::class, 'wipeAuthors'])->name('deepseek.wipe.authors');
    Route::get('deepseek/run', [AdminDeepSeekController::class, 'run'])->name('deepseek.run');
    Route::get('deepseek/run/authors', [AdminDeepSeekController::class, 'runAuthors'])->name('deepseek.run.authors');
    Route::get('deepseek/run/analyses', [AdminDeepSeekController::class, 'runAnalyses'])->name('deepseek.run.analyses');
    Route::get('deepseek/run/tags-seo', [AdminDeepSeekController::class, 'runTagsSeo'])->name('deepseek.run.tags-seo');
    Route::get('deepseek/run/poem-tags', [AdminDeepSeekController::class, 'runPoemTags'])->name('deepseek.run.poem-tags');
    Route::get('deepseek/log', [AdminDeepSeekController::class, 'log'])->name('deepseek.log');
    Route::post('seo/templates', [AdminSeoController::class, 'updateTemplates'])->name('seo.templates.update');
    Route::post('seo/pages', [AdminSeoController::class, 'storeSeoPage'])->name('seo.pages.store');
    Route::put('seo/pages/{seoPage}', [AdminSeoController::class, 'updateSeoPage'])->name('seo.pages.update');
    Route::delete('seo/pages/{seoPage}', [AdminSeoController::class, 'destroySeoPage'])->name('seo.pages.destroy');
    Route::post('seo/sitemap-refresh', [AdminSeoController::class, 'sitemapRefresh'])->name('seo.sitemap.refresh');
    Route::post('seo/counters', [AdminSeoController::class, 'updateCounters'])->name('seo.counters.update');
    Route::prefix('seo')->name('seo.')->group(function () {
        Route::resource('redirects', AdminUrlRedirectController::class)->except(['show']);
    });
});

Route::get('/{slug}/analiz', [PoemAnalysisController::class, 'show'])->name('poem.analysis');
Route::get('/{slug}', [SlugController::class, 'show'])->name('slug');
