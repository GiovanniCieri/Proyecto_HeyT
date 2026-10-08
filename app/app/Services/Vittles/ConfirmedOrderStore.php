<?php

namespace App\Services\Vittles;

use App\Support\DiagnosticLog;
use Illuminate\Support\Facades\DB;
use Throwable;

class ConfirmedOrderStore
{
    /** Registra eventos del historial sin mezclar su fallo con el resultado del POS. */
    public function __construct(private readonly DiagnosticLog $diagnostics) {}

    /**
     * Guarda o actualiza una orden ya confirmada, usando client_ref como clave local.
     * Es un historial para la demo; si SQLite falla, la confirmación remota sigue vigente.
     */
    public function record(array $result): void
    {
        try {
            $lines = $result['items'] ?? [['item' => $result['item'], 'quantity' => $result['quantity']]];
            DB::table('confirmed_orders')->upsert([[
                'client_ref' => $result['client_ref'],
                'order_id' => $result['order']['id'],
                'location_id' => $result['location']['id'],
                'location_name' => $result['location']['name'],
                'item_id' => $result['item']['id'],
                'item_name' => $result['item']['name'],
                'quantity' => $result['quantity'],
                'items_json' => json_encode(array_map(fn (array $line) => [
                    'id' => $line['item']['id'],
                    'name' => $line['item']['name'],
                    'quantity' => $line['quantity'],
                ], $lines), JSON_UNESCAPED_UNICODE),
                'total' => $result['order']['total'],
                'created_at' => now(),
                'updated_at' => now(),
            ]], ['client_ref'], [
                'order_id', 'location_id', 'location_name', 'item_id',
                'item_name', 'quantity', 'items_json', 'total', 'updated_at',
            ]);
            $this->diagnostics->event('info', 'order.history.saved', __METHOD__, [
                'client_ref' => $result['client_ref'],
                'order_id' => $result['order']['id'],
                'location_id' => $result['location']['id'],
            ]);
        } catch (Throwable $e) {
            // El historial local no participa en la confirmación de Vittles.
            $this->diagnostics->event('warning', 'order.history.failed', __METHOD__, ['error_type' => $e::class]);
        }
    }

    /** Lista solo sedes con confirmaciones locales para construir el filtro de pedidos. */
    public function locations(): array
    {
        return DB::table('confirmed_orders')
            ->select('location_id', 'location_name')
            ->distinct()
            ->orderBy('location_name')
            ->get()
            ->all();
    }

    /** Devuelve las últimas 100 confirmaciones locales, opcionalmente de una sede. */
    public function recent(?string $locationId): array
    {
        return DB::table('confirmed_orders')
            ->when($locationId !== null, fn ($query) => $query->where('location_id', $locationId))
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get()
            ->all();
    }

    /** Busca el comprobante local por la referencia mostrada tras crear la orden. */
    public function findByReference(string $clientRef): ?object
    {
        return DB::table('confirmed_orders')->where('client_ref', $clientRef)->first();
    }
}
