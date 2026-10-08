<?php

namespace App\Http\Middleware;

use App\Support\DiagnosticLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TraceWebRequest
{
    public function __construct(private readonly DiagnosticLog $diagnostics) {}

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('correlation_id', bin2hex(random_bytes(8)));
        $started = microtime(true);
        $details = [
            'method' => $request->method(),
            'path' => '/'.$request->path(),
            'route' => $request->route()?->getName() ?? 'unknown',
        ];
        $this->diagnostics->event('info', 'web.request.started', __METHOD__, $details);

        try {
            $response = $next($request);
            $response->headers->set('X-Diagnostic-Id', $this->diagnostics->correlationId());
            $this->diagnostics->event('info', 'web.request.finished', __METHOD__, $details + [
                'http_status' => $response->getStatusCode(),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ]);

            return $response;
        } catch (Throwable $e) {
            $this->diagnostics->event('error', 'web.request.failed', __METHOD__, $details + [
                'error_type' => $e::class,
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ]);

            throw $e;
        }
    }
}
