<?php

namespace App\Services\Vittles;

use App\Support\DiagnosticLog;
use Illuminate\Support\Facades\DB;
use Throwable;

class ConfirmedOrderStore
{
    public function __construct(private readonly DiagnosticLog $diagnostics) {}

    public function record(array $result): void
    {
        try {
            DB::table('confirmed_orders')->upsert([[
                'client_ref' => $result['client_ref'],
                'order_id' => $result['order']['id'],
                'location_id' => $result['location']['id'],
                'location_name' => $result['location']['name'],
                'item_id' => $result['item']['id'],
                'item_name' => $result['item']['name'],
                'quantity' => $result['quantity'],
                'total' => $result['order']['total'],
                'created_at' => now(),
                'updated_at' => now(),
            ]], ['client_ref'], [
                'order_id', 'location_id', 'location_name', 'item_id',
                'item_name', 'quantity', 'total', 'updated_at',
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

    public function locations(): array
    {
        return DB::table('confirmed_orders')
            ->select('location_id', 'location_name')
            ->distinct()
            ->orderBy('location_name')
            ->get()
            ->all();
    }

    public function recent(?string $locationId): array
    {
        return DB::table('confirmed_orders')
            ->when($locationId !== null, fn ($query) => $query->where('location_id', $locationId))
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get()
            ->all();
    }
}
