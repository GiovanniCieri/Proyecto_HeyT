<?php

namespace App\Services\Vittles;

use App\Support\DiagnosticLog;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class OrderService
{
    public function __construct(
        private readonly VittlesClient $client,
        private readonly CatalogService $catalog,
        private readonly DiagnosticLog $diagnostics,
        private readonly ConfirmedOrderStore $confirmedOrders,
    ) {}

    public function place(string $locationId, string $itemName, string $requestKey = 'demo', int $quantity = 2): array
    {
        return $this->placeSelection($locationId, [['name' => $itemName, 'quantity' => $quantity]], $requestKey);
    }

    public function placeMany(string $locationId, array $lines, string $requestKey = 'demo'): array
    {
        return $this->placeSelection($locationId, $lines, $requestKey);
    }

    private function placeSelection(string $locationId, array $lines, string $requestKey): array
    {
        $this->diagnostics->event('info', 'order.place.started', __METHOD__, ['items_count' => count($lines)]);
        $locationId = trim($locationId);
        $requestKey = trim($requestKey);
        if ($locationId === '' || $requestKey === '' || count($lines) === 0 || count($lines) > 20) {
            $this->diagnostics->event('warning', 'order.validation_failed', __METHOD__, ['reason_code' => 'missing_input']);
            throw new InvalidArgumentException('Elegí una location, entre 1 y 20 productos y una clave de solicitud.');
        }

        $catalog = $this->catalog->load();
        $matches = array_values(array_filter($catalog['locations'], fn (array $location) => $location['id'] === $locationId));
        if (count($matches) !== 1) {
            $this->diagnostics->event('warning', 'order.validation_failed', __METHOD__, ['reason_code' => 'location_unknown']);
            throw new InvalidArgumentException('La location '.$locationId.' no existe.');
        }

        $menu = $catalog['menus'][$locationId] ?? null;
        if (($menu['status'] ?? null) !== 'loaded') {
            $this->diagnostics->event('warning', 'order.validation_failed', __METHOD__, ['location_id' => $locationId, 'reason_code' => 'menu_unreadable']);
            throw new InvalidArgumentException('No se pudo leer el menú de '.$locationId.': '.($menu['message'] ?? 'sin información').'.');
        }

        $resolved = [];
        $seen = [];
        foreach ($lines as $line) {
            $quantity = $line['quantity'] ?? null;
            if (! is_int($quantity) || $quantity < 1 || $quantity > 20) {
                $this->diagnostics->event('warning', 'order.validation_failed', __METHOD__, ['reason_code' => 'quantity_out_of_range']);
                throw new InvalidArgumentException('Cada cantidad debe estar entre 1 y 20.');
            }
            $name = $line['name'] ?? null;
            $id = $line['item_id'] ?? null;
            if ((! is_string($name) || trim($name) === '') && (! is_string($id) || $id === '')) {
                throw new InvalidArgumentException('Cada producto debe tener nombre exacto o ID de menú.');
            }
            $items = array_values(array_filter($menu['items'], fn (array $item) => $id !== null ? $item['id'] === $id : $item['name'] === trim($name)));
            if (count($items) !== 1) {
                $reason = count($items) === 0 ? 'item_unknown' : 'item_ambiguous';
                $this->diagnostics->event('warning', 'order.validation_failed', __METHOD__, ['location_id' => $locationId, 'reason_code' => $reason]);
                throw new InvalidArgumentException(count($items) === 0 ? 'Un producto no existe en el menú de '.$locationId.'.' : 'Hay varios productos con ese nombre exacto en '.$locationId.'.');
            }
            $item = $items[0];
            if (! $item['available']) {
                $this->diagnostics->event('warning', 'order.validation_failed', __METHOD__, ['location_id' => $locationId, 'reason_code' => 'item_unavailable']);
                throw new InvalidArgumentException('El producto «'.$item['name'].'» no está disponible en '.$locationId.'.');
            }
            if (isset($seen[$item['id']])) {
                throw new InvalidArgumentException('El mismo producto aparece dos veces en el pedido.');
            }
            $seen[$item['id']] = true;
            $resolved[] = ['item' => $item, 'quantity' => $quantity];
        }

        $canonical = array_map(fn (array $line) => [$line['item']['id'], $line['quantity']], $resolved);
        usort($canonical, fn (array $a, array $b) => strcmp($a[0], $b[0]));
        $referenceParts = count($resolved) === 1
            ? [$requestKey, $locationId, $resolved[0]['item']['name'], $resolved[0]['quantity']]
            : ['multi-v1', $requestKey, $locationId, $canonical];
        $ref = 'heyt-'.substr(hash('sha256', json_encode($referenceParts, JSON_UNESCAPED_UNICODE)), 0, 32);
        $this->diagnostics->event('info', 'order.reference.ready', __METHOD__, ['location_id' => $locationId, 'client_ref' => $ref]);
        $context = [
            'location' => $matches[0], 'item' => $resolved[0]['item'],
            'items' => $resolved, 'quantity' => array_sum(array_column($resolved, 'quantity')),
            'client_ref' => $ref, 'catalog' => $catalog,
        ];

        try {
            $result = Cache::store('file')->lock('vittles-order-'.$ref, 20)->block(8, function () use ($ref, $locationId, $resolved, $context) {
                $found = $this->findByReference($ref);
                if (count($found) > 1) {
                    $this->diagnostics->event('warning', 'order.lookup.ambiguous', __METHOD__, ['client_ref' => $ref, 'items_count' => count($found)]);

                    return $context + ['status' => 'UNKNOWN', 'message' => 'Vittles devolvió varias órdenes para la misma referencia.'];
                }
                if (count($found) === 1) {
                    return $context + ['status' => 'EXISTING', 'order' => $found[0]];
                }

                try {
                    $this->diagnostics->event('info', 'order.post.started', __METHOD__, ['location_id' => $locationId, 'client_ref' => $ref]);
                    $order = $this->client->createOrder([
                        'location_id' => $locationId,
                        'client_ref' => $ref,
                        'items' => array_map(fn (array $line) => ['item_id' => $line['item']['id'], 'quantity' => $line['quantity']], $resolved),
                    ], $locationId);
                } catch (UnknownOutcome $e) {
                    $this->diagnostics->event('warning', 'order.post.uncertain', __METHOD__, ['client_ref' => $ref, 'error_type' => $e::class]);

                    return $this->reconcileUnknown($context, $ref, $e->getMessage());
                }

                if (($order['status'] ?? null) === 'REJECTED') {
                    $this->diagnostics->event('warning', 'order.post.rejected', __METHOD__, ['client_ref' => $ref, 'reason_code' => 'provider_rejected']);

                    return $context + ['status' => 'REJECTED', 'message' => (string) ($order['reason'] ?? 'Vittles rechazó la orden.')];
                }
                if (($order['status'] ?? null) !== 'ACCEPTED') {
                    $this->diagnostics->event('warning', 'order.post.uncertain', __METHOD__, ['client_ref' => $ref, 'reason_code' => 'unexpected_status']);

                    return $this->reconcileUnknown($context, $ref, 'La respuesta del POST no confirma una orden aceptada.');
                }

                try {
                    return $context + ['status' => 'CREATED', 'order' => $this->validateOrder($order, $ref)];
                } catch (VittlesException $e) {
                    $this->diagnostics->event('warning', 'order.post.uncertain', __METHOD__, ['client_ref' => $ref, 'reason_code' => 'invalid_order']);

                    return $this->reconcileUnknown($context, $ref, $e->getMessage());
                }
            });
            $this->diagnostics->event('info', 'order.place.finished', __METHOD__, [
                'client_ref' => $ref,
                'result' => $result['status'],
                'order_id' => $result['order']['id'] ?? null,
            ]);
            if (in_array($result['status'], ['CREATED', 'EXISTING', 'RECOVERED'], true)) {
                $this->confirmedOrders->record($result);
            }

            return $result;
        } catch (LockTimeoutException) {
            $this->diagnostics->event('warning', 'order.place.finished', __METHOD__, ['client_ref' => $ref, 'result' => 'UNKNOWN', 'reason_code' => 'lock_timeout']);

            return $context + ['status' => 'UNKNOWN', 'message' => 'Otra ejecución está procesando esta referencia; consultá el resultado antes de reintentar.'];
        }
    }

    private function reconcileUnknown(array $context, string $ref, string $message): array
    {
        $this->diagnostics->event('warning', 'order.reconcile.started', __METHOD__, ['client_ref' => $ref]);
        try {
            $found = $this->findByReference($ref);
            if (count($found) === 1) {
                $this->diagnostics->event('info', 'order.reconcile.recovered', __METHOD__, ['client_ref' => $ref, 'order_id' => $found[0]['id']]);

                return $context + ['status' => 'RECOVERED', 'order' => $found[0]];
            }
        } catch (VittlesException) {
            // Si falla la consulta, la operación sigue siendo incierta.
        }

        $this->diagnostics->event('warning', 'order.reconcile.unknown', __METHOD__, ['client_ref' => $ref]);

        return $context + ['status' => 'UNKNOWN', 'message' => $message.' No se enviará otro POST automáticamente.'];
    }

    private function findByReference(string $ref): array
    {
        $response = $this->client->get('/v1/orders', ['client_ref' => $ref]);
        if (! is_array($response['data'] ?? null) || ! array_is_list($response['data'])) {
            $this->diagnostics->event('error', 'order.contract_invalid', __METHOD__, ['reason_code' => 'lookup_data']);
            throw new VittlesException('La búsqueda de órdenes devolvió un formato inesperado.');
        }

        $this->diagnostics->event('info', 'order.lookup.finished', __METHOD__, ['client_ref' => $ref, 'items_count' => count($response['data'])]);

        return array_map(fn (mixed $order) => $this->validateOrder($order, $ref), $response['data']);
    }

    private function validateOrder(mixed $order, string $ref): array
    {
        if (! is_array($order) || ! is_string($order['id'] ?? null)
            || ($order['client_ref'] ?? null) !== $ref
            || ! is_numeric($order['total'] ?? null)
            || ($order['status'] ?? null) !== 'ACCEPTED') {
            $this->diagnostics->event('error', 'order.contract_invalid', __METHOD__, ['reason_code' => 'order_shape']);
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
