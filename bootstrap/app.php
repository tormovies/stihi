<?php

use App\Models\GonePath;
use App\Models\SiteSetting;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            \App\Http\Middleware\BlockedIps::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\GzipResponse::class,
            \App\Http\Middleware\LogBotRequests::class,
            \App\Http\Middleware\Log404Requests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (NotFoundHttpException $e): ?\Illuminate\Http\Response {
            $request = request();
            $path = $request->path();
            if ($path !== '' && GonePath::isGone($path)) {
                \App\Support\Log410::write($request);
                View::share('skipCounter', true);
                return response()->view('errors.410', [], 410);
            }
            try {
                if (SiteSetting::get('counter_show_on_404', 'off') !== 'on') {
                    View::share('skipCounter', true);
                }
            } catch (\Throwable $t) {
                View::share('skipCounter', true);
            }
            return null;
        });
    })->create();
