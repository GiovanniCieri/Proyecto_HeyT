<?php

namespace App\Console\Commands;

use App\Services\Vittles\VittlesClient;
use App\Services\Vittles\VittlesException;
use Illuminate\Console\Command;

class ShowVittlesOrder extends Command
{
    protected $signature = 'vittles:show {order_id : ID de la orden en Vittles}';

    protected $description = 'Consulta una orden por ID directamente en Vittles';

    public function handle(VittlesClient $client): int
    {
        $id = trim((string) $this->argument('order_id'));
        if ($id === '') {
            $this->error('Indicá el ID de la orden.');

            return self::FAILURE;
        }

        try {
            $order = $client->get('/v1/orders/'.rawurlencode($id));
        } catch (VittlesException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (($order['id'] ?? null) !== $id || ! is_string($order['status'] ?? null) || ! is_numeric($order['total'] ?? null)) {
            $this->error('La respuesta de Vittles no tiene el formato esperado para esta orden.');

            return self::FAILURE;
        }

        $this->line('Fuente: Vittles (consulta en vivo)');
        $this->line('ID: '.$order['id']);
        $this->line('Estado: '.$order['status']);
        $this->line('Total Vittles: $'.number_format((float) $order['total'], 2, '.', ''));
        $this->line('Referencia: '.($order['client_ref'] ?? 'sin referencia'));

        return self::SUCCESS;
    }
}
