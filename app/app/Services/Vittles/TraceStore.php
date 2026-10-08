<?php

namespace App\Services\Vittles;

use App\Support\DiagnosticLog;
use Illuminate\Http\Client\Response;
use Throwable;

class TraceStore
{
    public function __construct(private readonly DiagnosticLog $diagnostics) {}

    public function record(
        string $method,
        string $path,
        array $query,
        array $requestBody,
        array $requestHeaders,
        ?Response $response,
        int $durationMs,
        int $attempt,
        ?string $transportError = null,
        ?string $operationId = null,
        ?string $source = null,
    ): void {
        try {
            $body = $response?->json();
            $entry = [
                'id' => bin2hex(random_bytes(6)),
                'correlation_id' => $this->diagnostics->correlationId(),
                'operation_id' => $operationId,
                'source' => $source,
                'at' => now()->toIso8601String(),
                'method' => $method,
                'path' => $path,
                'query' => $this->redact($query),
                'attempt' => $attempt,
                'request_headers' => ['Authorization' => $path === '/oauth/token' ? 'none' : '[REDACTED]'] + $this->redact($requestHeaders),
                'request_body' => $this->redact($requestBody),
                'status' => $response?->status(),
                'duration_ms' => $durationMs,
                'response_headers' => $response ? $this->selectedHeaders($response) : [],
                'response_body' => is_array($body) ? $this->redact($body) : ['note' => $response ? 'Respuesta no JSON omitida.' : 'Sin respuesta.'],
                'transport_error' => $transportError,
            ];

            $this->append($entry);
            $this->diagnostics->event(
                $transportError !== null || ($response?->status() ?? 0) >= 400 || (is_array($body) && ($body['status'] ?? null) === 'REJECTED') ? 'warning' : 'info',
                'vittles.http.attempt',
                $source ?? __METHOD__,
                [
                    'method' => $method,
                    'path' => $path,
                    'http_status' => $response?->status(),
                    'duration_ms' => $durationMs,
                    'attempt' => $attempt,
                    'operation_id' => $operationId,
                    'trace_id' => is_array($body) ? ($body['trace_id'] ?? null) : null,
                    'result' => is_array($body) ? ($body['status'] ?? null) : null,
                    'reason_code' => $transportError === null ? null : 'connection_interrupted',
                ],
            );
        } catch (Throwable) {
            // La telemetría no debe cambiar el resultado de una orden.
        }
    }

    public function recent(int $limit = 120): array
    {
        $path = $this->path();
        if (! is_file($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        $entries = [];
        foreach (array_reverse(array_slice($lines, -$limit)) as $line) {
            $entry = json_decode($line, true);
            if (is_array($entry)) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    private function append(array $entry): void
    {
        $path = $this->path();
        $handle = fopen($path, 'c+');
        if ($handle === false) {
            return;
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                return;
            }
            fseek($handle, 0, SEEK_END);
            fwrite($handle, json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)."\n");
            if (ftell($handle) > 2_000_000) {
                rewind($handle);
                $lines = explode("\n", (string) stream_get_contents($handle));
                $kept = implode("\n", array_slice(array_filter($lines, fn (string $line) => $line !== ''), -250))."\n";
                ftruncate($handle, 0);
                rewind($handle);
                fwrite($handle, $kept);
            }
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    private function selectedHeaders(Response $response): array
    {
        $result = [];
        foreach (['Content-Type', 'Retry-After-Ms', 'Retry-After', 'X-Request-Id'] as $name) {
            $value = $response->header($name);
            if ($value !== null) {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    private function redact(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && preg_match('/secret|token|authorization|password|phone|email|customer|cookie/i', $key)) {
                $result[$key] = '[REDACTED]';
            } else {
                $result[$key] = is_array($value) ? $this->redact($value) : $value;
            }
        }

        return $result;
    }

    private function path(): string
    {
        return app()->environment('testing')
            ? storage_path('framework/testing/vittles-traces.jsonl')
            : storage_path('app/private/vittles-traces.jsonl');
    }
}
