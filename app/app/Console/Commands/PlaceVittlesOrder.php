<?php

namespace App\Console\Commands;

use App\Services\Vittles\OrderService;
use App\Services\Vittles\VittlesException;
use Illuminate\Console\Command;
use InvalidArgumentException;

class PlaceVittlesOrder extends Command
{
    protected $signature = 'vittles:order {location : ID de la location} {item : Nombre exacto del producto} {--request-key=demo : Identificador de la intención de compra}';

    protected $description = 'Consulta todas las sedes y menús, y crea o recupera una orden de cantidad 2';

    public function handle(OrderService $orders): int
    {
        try {
            $result = $orders->place((string) $this->argument('location'), (string) $this->argument('item'), (string) $this->option('request-key'));
        } catch (InvalidArgumentException|VittlesException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $loaded = count(array_filter($result['catalog']['menus'], fn (array $menu) => $menu['status'] === 'loaded'));
        $this->line('Locations: '.count($result['catalog']['locations']).' | Menús consultados: '.count($result['catalog']['menus']).' | Cargados: '.$loaded);
        $this->line('Estado: '.$result['status']);
        $this->line('Location: '.$result['location']['name'].' ('.$result['location']['id'].')');
        $this->line('Producto: '.$result['item']['name'].' × '.$result['quantity']);
        $this->line('Referencia: '.$result['client_ref']);

        if (isset($result['order'])) {
            $this->line('ID: '.$result['order']['id']);
            $this->line('Total Vittles: $'.$result['order']['total']);
        } else {
            $this->warn($result['message'] ?? 'No hay orden confirmada.');
        }

        return in_array($result['status'], ['CREATED', 'EXISTING', 'RECOVERED'], true) ? self::SUCCESS : self::FAILURE;
    }
}
