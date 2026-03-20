<?php

namespace App\Http\Middleware;

use App\Models\UrlRedirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 301 с сохранённых from_path на to_path (только GET/HEAD).
 * Пути в БД без ведущего и завершающего слэша; совпадение с $request->path() после нормализации.
 */
class UrlRedirectMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }
        if ($request->is('admin') || $request->is('admin/*')) {
            return $next($request);
        }

        $path = UrlRedirect::normalizePath($request->path());
        if ($path === '') {
            return $next($request);
        }

        $map = UrlRedirect::redirectMap();
        if (!isset($map[$path])) {
            return $next($request);
        }

        $to = $map[$path];
        $url = UrlRedirect::targetUrl($to);

        return redirect()->to($url, 301);
    }
}
