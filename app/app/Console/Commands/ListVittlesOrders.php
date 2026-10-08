<?php

namespace App\Console\Commands;

use App\Services\Vittles\ConfirmedOrderStore;
use Illuminate\Console\Command;
use Throwable;

class ListVittlesOrders extends Command
{
    protected $signature = 'vittles:orders {location? : Filtrar el historial local por ID de location}';

    protected $description = 'Lista hasta 100 pedidos confirmados por esta integración (historial local)';

    public function handle(ConfirmedOrderStore $confirmedOrders): int
    {
        $location = $this->argument('location');
        $location = is_string($location) && trim($location) !== '' ? trim($location) : null;

        try {
            $orders = $confirmedOrders->recent($location);
        } catch (Throwable) {
            $this->error('No se pudo leer el historial local. Ejecutá php artisan migrate.');

            return self::FAILURE;
        }

        $this->line('Fuente: historial local de esta integración; no es un listado completo de Vittles.');
        if ($orders === []) {
            $this->warn('No hay pedidos confirmados registrados'.($location ? ' en '.$location : '').'.');

            return self::SUCCESS;
        }

        $this->table(['ID', 'Location', 'Productos', 'Unidades', 'Total'], array_map(fn (object $order) => [
            $order->order_id,
            $order->location_id,
            count(json_decode($order->items_json ?? '', true) ?: []) ?: 1,
            $order->quantity,
            '$'.number_format((float) $order->total, 2, '.', ''),
        ], $orders));

        return self::SUCCESS;
    }
}
