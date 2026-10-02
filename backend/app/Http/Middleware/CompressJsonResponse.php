<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Compresse en gzip les réponses JSON volumineuses.
 * nginx en production ne compresse que le text/html (gzip_types commenté) :
 * sans cela, les données de la carte (~6 Mo) partent non compressées.
 */
class CompressJsonResponse
{
    private const MIN_BYTES = 8 * 1024;

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            $response instanceof BinaryFileResponse
            || $response instanceof StreamedResponse
            || $response->headers->has('Content-Encoding')
            || !str_contains((string) $request->header('Accept-Encoding'), 'gzip')
            || !str_contains((string) $response->headers->get('Content-Type'), 'json')
            || !function_exists('gzencode')
        ) {
            return $response;
        }

        $content = $response->getContent();

        if ($content === false || strlen($content) < self::MIN_BYTES) {
            return $response;
        }

        $compressed = gzencode($content, 5);

        if ($compressed === false) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', (string) strlen($compressed));
        $response->setVary(array_unique(array_merge($response->getVary(), ['Accept-Encoding'])));

        return $response;
    }
}
