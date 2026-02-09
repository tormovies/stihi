<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GzipResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!function_exists('gzencode')) {
            return $response;
        }

        $acceptEncoding = $request->header('Accept-Encoding', '');
        if (!str_contains($acceptEncoding, 'gzip')) {
            return $response;
        }

        $contentType = $response->headers->get('Content-Type', '');
        $compressible = str_contains($contentType, 'text/html')
            || str_contains($contentType, 'text/css')
            || str_contains($contentType, 'text/plain')
            || str_contains($contentType, 'application/javascript')
            || str_contains($contentType, 'application/json')
            || str_contains($contentType, 'application/xml');

        if (!$compressible || $response->getContent() === false) {
            return $response;
        }

        $content = $response->getContent();
        if ($content === '' || strlen($content) < 256) {
            return $response;
        }

        $compressed = @gzencode($content, 6);
        if ($compressed === false) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Vary', 'Accept-Encoding');
        $response->headers->remove('Content-Length');

        return $response;
    }
}
