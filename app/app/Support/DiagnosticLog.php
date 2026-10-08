<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

class DiagnosticLog
{
    private string $runId;

    private const FIELDS = [
        'action', 'attempt', 'cache_hit', 'client_ref', 'duration_ms', 'error_type',
        'http_status', 'items_count', 'location_id', 'locations_count',
        'menus_count', 'method', 'operation_id', 'order_id', 'path',
        'probe', 'quantity', 'reason_code', 'result', 'route', 'status', 'trace_id',
        'user_id',
    ];

    /** Crea un ID para comandos CLI y procesos sin request web. */
    public function __construct()
    {
        $this->runId = bin2hex(random_bytes(8));
    }

    /** Usa el ID de la request web cuando existe; de otro modo, el del proceso. */
    public function correlationId(): string
    {
        $id = app()->bound('request') ? request()->attributes->get('correlation_id') : null;

        return is_string($id) && $id !== '' ? $id : $this->runId;
    }

    /**
     * Registra solo campos permitidos y escalares para poder buscar fallos por método
     * sin guardar secretos o cuerpos completos; un fallo del log no corta la operación.
     */
    public function event(string $level, string $event, string $source, array $details = []): void
    {
        try {
            $context = [
                'correlation_id' => $this->correlationId(),
                'event' => $event,
                'source' => $source,
            ];
            foreach (self::FIELDS as $field) {
                if (array_key_exists($field, $details) && is_scalar($details[$field])) {
                    $context[$field] = $details[$field];
                }
            }
            Log::channel('integration')->log($level, $event, $context);
        } catch (Throwable) {
            // Los diagnósticos no deben alterar una orden ni un login.
        }
    }
}
