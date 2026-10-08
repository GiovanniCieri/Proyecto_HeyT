<?php

namespace App\Services\Vittles;

use App\Support\DiagnosticLog;

class CatalogService
{
    /** Usa el cliente HTTP compartido y un ID diagnóstico para cada lectura del catálogo. */
    public function __construct(
        private readonly VittlesClient $client,
        private readonly DiagnosticLog $diagnostics,
    ) {}

    /**
     * Reúne todas las páginas de locations y consulta el menú de cada una.
     * La API real pagina de 2 en 2 y una sede inactiva responde 403: se conserva
     * ese estado por sede para cumplir la lectura global sin crear allí pedidos.
     */
    public function load(): array
    {
        $this->diagnostics->event('info', 'catalog.load.started', __METHOD__);
        $locations = [];
        $cursor = null;
        $seen = [];

        do {
            $query = $cursor === null ? [] : ['cursor' => $cursor];
            $page = $this->client->get('/v1/locations', $query);
            if (! is_array($page['data'] ?? null) || ! array_is_list($page['data'])) {
                $this->diagnostics->event('error', 'catalog.contract_invalid', __METHOD__, ['reason_code' => 'locations_data']);
                throw new VittlesException('La página de locations tiene un formato inesperado.');
            }

            $this->diagnostics->event('info', 'catalog.locations.page', __METHOD__, ['items_count' => count($page['data'])]);

            foreach ($page['data'] as $location) {
                if (! is_array($location) || ! is_string($location['id'] ?? null) || ! is_string($location['name'] ?? null)) {
                    $this->diagnostics->event('error', 'catalog.contract_invalid', __METHOD__, ['reason_code' => 'location_item']);
                    throw new VittlesException('Una location tiene un formato inesperado.');
                }
                $locations[] = $location;
            }

            // API_DOCS.md omite la paginación: solo se siguen cursores entregados por Vittles.
            $cursor = $page['next_cursor'] ?? null;
            if ($cursor !== null && (! is_string($cursor) || isset($seen[$cursor]))) {
                $this->diagnostics->event('error', 'catalog.contract_invalid', __METHOD__, ['reason_code' => 'cursor_invalid']);
                throw new VittlesException('La paginación de locations devolvió un cursor inválido o repetido.');
            }
            if ($cursor !== null) {
                $seen[$cursor] = true;
            }
            if (count($seen) > 100) {
                $this->diagnostics->event('error', 'catalog.contract_invalid', __METHOD__, ['reason_code' => 'too_many_pages']);
                throw new VittlesException('La paginación de locations excedió el límite de seguridad.');
            }
        } while ($cursor !== null);

        $menus = [];
        foreach ($locations as $location) {
            $id = $location['id'];
            try {
                $menu = $this->client->get('/v1/locations/'.rawurlencode($id).'/menu');
                if (! is_array($menu['menuItems'] ?? null) || ! array_is_list($menu['menuItems'])) {
                    $this->diagnostics->event('error', 'catalog.contract_invalid', __METHOD__, ['location_id' => $id, 'reason_code' => 'menu_items']);
                    throw new VittlesException('El menú de '.$id.' tiene un formato inesperado.');
                }
                $items = [];
                foreach ($menu['menuItems'] as $item) {
                    $items[] = $this->normalizeItem($item, $id);
                }
                $menus[$id] = ['status' => 'loaded', 'items' => $items];
                $this->diagnostics->event('info', 'catalog.menu.loaded', __METHOD__, ['location_id' => $id, 'items_count' => count($items)]);
            } catch (VittlesException $e) {
                // Una sede inactiva da 403 y un menú puede fallar con 500; se informa por sede.
                $menus[$id] = [
                    'status' => $e->httpStatus === 403 ? 'forbidden' : 'error',
                    'items' => [],
                    'message' => $e->getMessage(),
                ];
                $this->diagnostics->event('warning', 'catalog.menu.failed', __METHOD__, ['location_id' => $id, 'http_status' => $e->httpStatus, 'error_type' => $e::class]);
            }
        }

        $this->diagnostics->event('info', 'catalog.load.finished', __METHOD__, ['locations_count' => count($locations), 'menus_count' => count($menus)]);

        return ['locations' => $locations, 'menus' => $menus];
    }

    /**
     * Traduce las variantes observadas del mock a un formato único para el resto del sistema.
     * Acepta precio numérico o string numérico y disponibilidad bool o 0/1;
     * cualquier otra forma falla explícitamente para no inventar precios o stock.
     */
    private function normalizeItem(mixed $item, string $locationId): array
    {
        if (! is_array($item) || ! is_string($item['id'] ?? null) || ! is_string($item['name'] ?? null)
            || ! is_numeric($item['price'] ?? null) || ! in_array($item['available'] ?? null, [true, false, 0, 1], true)) {
            $this->diagnostics->event('error', 'catalog.contract_invalid', __METHOD__, ['location_id' => $locationId, 'reason_code' => 'menu_item']);
            throw new VittlesException('Un producto de '.$locationId.' tiene un formato inesperado.');
        }

        return [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => number_format((float) $item['price'], 2, '.', ''),
            'available' => $item['available'] === true || $item['available'] === 1,
        ];
    }
}
