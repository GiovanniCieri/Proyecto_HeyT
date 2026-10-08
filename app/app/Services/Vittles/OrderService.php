<?php

namespace App\Services\Vittles;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class OrderService
{
    public function __construct(
        private readonly VittlesClient $client,
        private readonly CatalogService $catalog,
    ) {}

    public function place(string $locationId, string $itemName, string $requestKey = 'demo'): array
    {
        $locationId = trim($locationId);
        $itemName = trim($itemName);
        $requestKey = trim($requestKey);
        if ($locationId === '' || $itemName === '' || $requestKey === '') {
            throw new InvalidArgumentException('Location, nombre de producto y clave de solicitud son obligatorios.');
        }

        $catalog = $this->catalog->load();
        $matches = array_values(array_filter($catalog['locations'], fn (array $location) => $location['id'] === $locationId));
        if (count($matches) !== 1) {
            throw new InvalidArgumentException('La location '.$locationId.' no existe.');
        }

        $menu = $catalog['menus'][$locationId] ?? null;
        if (($menu['status'] ?? null) !== 'loaded') {
            throw new InvalidArgumentException('No se pudo leer el menú de '.$locationId.': '.($menu['message'] ?? 'sin información').'.');
        }

        $items = array_values(array_filter($menu['items'], fn (array $item) => $item['name'] === $itemName));
        if (count($items) === 0) {
            throw new InvalidArgumentException('El producto «'.$itemName.'» no existe en el menú de '.$locationId.'.');
        }
        if (count($items) > 1) {
            throw new InvalidArgumentException('Hay varios productos con ese nombre exacto en '.$locationId.'.');
        }
        $item = $items[0];
        if (! $item['available']) {
            throw new InvalidArgumentException('El producto «'.$itemName.'» no está disponible en '.$locationId.'.');
        }

        $ref = 'heyt-'.substr(hash('sha256', json_encode([$requestKey, $locationId, $itemName, 2], JSON_UNESCAPED_UNICODE)), 0, 32);
        $context = [
            'location' => $matches[0], 'item' => $item, 'quantity' => 2,
            'client_ref' => $ref, 'catalog' => $catalog,
        ];

        try {
            return Cache::store('file')->lock('vittles-order-'.$ref, 20)->block(8, function () use ($ref, $locationId, $item, $context) {
                $found = $this->findByReference($ref);
                if (count($found) > 1) {
                    return $context + ['status' => 'UNKNOWN', 'message' => 'Vittles devolvió varias órdenes para la misma referencia.'];
                }
                if (count($found) === 1) {
                    return $context + ['status' => 'EXISTING', 'order' => $found[0]];
                }

                try {
                    $order = $this->client->createOrder([
                        'location_id' => $locationId,
                        'client_ref' => $ref,
                        'items' => [['item_id' => $item['id'], 'quantity' => 2]],
                    ], $locationId);
                } catch (UnknownOutcome $e) {
                    return $this->reconcileUnknown($context, $ref, $e->getMessage());
                }

                if (($order['status'] ?? null) === 'REJECTED') {
                    return $context + ['status' => 'REJECTED', 'message' => (string) ($order['reason'] ?? 'Vittles rechazó la orden.')];
                }
                if (($order['status'] ?? null) !== 'ACCEPTED') {
                    return $this->reconcileUnknown($context, $ref, 'La respuesta del POST no confirma una orden aceptada.');
                }

                try {
                    return $context + ['status' => 'CREATED', 'order' => $this->validateOrder($order, $ref)];
                } catch (VittlesException $e) {
                    return $this->reconcileUnknown($context, $ref, $e->getMessage());
                }
            });
        } catch (LockTimeoutException) {
            return $context + ['status' => 'UNKNOWN', 'message' => 'Otra ejecución está procesando esta referencia; consultá el resultado antes de reintentar.'];
        }
    }

    private function reconcileUnknown(array $context, string $ref, string $message): array
    {
        try {
            $found = $this->findByReference($ref);
            if (count($found) === 1) {
                return $context + ['status' => 'RECOVERED', 'order' => $found[0]];
            }
        } catch (VittlesException) {
            // Si falla la consulta, la operación sigue siendo incierta.
        }

        return $context + ['status' => 'UNKNOWN', 'message' => $message.' No se enviará otro POST automáticamente.'];
    }

    private function findByReference(string $ref): array
    {
        $response = $this->client->get('/v1/orders', ['client_ref' => $ref]);
        if (! is_array($response['data'] ?? null) || ! array_is_list($response['data'])) {
            throw new VittlesException('La búsqueda de órdenes devolvió un formato inesperado.');
        }

        return array_map(fn (mixed $order) => $this->validateOrder($order, $ref), $response['data']);
    }

    private function validateOrder(mixed $order, string $ref): array
    {
        if (! is_array($order) || ! is_string($order['id'] ?? null)
            || ($order['client_ref'] ?? null) !== $ref
            || ! is_numeric($order['total'] ?? null)
            || ($order['status'] ?? null) !== 'ACCEPTED') {
            throw new VittlesException('La orden devuelta por Vittles no cumple el contrato observado.');
        }

        return [
            'id' => $order['id'],
            'status' => $order['status'],
            'total' => number_format((float) $order['total'], 2, '.', ''),
            'client_ref' => $ref,
        ];
    }
}
