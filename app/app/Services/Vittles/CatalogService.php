<?php

namespace App\Services\Vittles;

class CatalogService
{
    public function __construct(private readonly VittlesClient $client) {}

    public function load(): array
    {
        $locations = [];
        $cursor = null;
        $seen = [];

        do {
            $query = $cursor === null ? [] : ['cursor' => $cursor];
            $page = $this->client->get('/v1/locations', $query);
            if (! is_array($page['data'] ?? null) || ! array_is_list($page['data'])) {
                throw new VittlesException('La página de locations tiene un formato inesperado.');
            }

            foreach ($page['data'] as $location) {
                if (! is_array($location) || ! is_string($location['id'] ?? null) || ! is_string($location['name'] ?? null)) {
                    throw new VittlesException('Una location tiene un formato inesperado.');
                }
                $locations[] = $location;
            }

            $cursor = $page['next_cursor'] ?? null;
            if ($cursor !== null && (! is_string($cursor) || isset($seen[$cursor]))) {
                throw new VittlesException('La paginación de locations devolvió un cursor inválido o repetido.');
            }
            if ($cursor !== null) {
                $seen[$cursor] = true;
            }
            if (count($seen) > 100) {
                throw new VittlesException('La paginación de locations excedió el límite de seguridad.');
            }
        } while ($cursor !== null);

        $menus = [];
        foreach ($locations as $location) {
            $id = $location['id'];
            try {
                $menu = $this->client->get('/v1/locations/'.rawurlencode($id).'/menu');
                if (! is_array($menu['menuItems'] ?? null) || ! array_is_list($menu['menuItems'])) {
                    throw new VittlesException('El menú de '.$id.' tiene un formato inesperado.');
                }
                $items = [];
                foreach ($menu['menuItems'] as $item) {
                    $items[] = $this->normalizeItem($item, $id);
                }
                $menus[$id] = ['status' => 'loaded', 'items' => $items];
            } catch (VittlesException $e) {
                $menus[$id] = [
                    'status' => $e->httpStatus === 403 ? 'forbidden' : 'error',
                    'items' => [],
                    'message' => $e->getMessage(),
                ];
            }
        }

        return ['locations' => $locations, 'menus' => $menus];
    }

    private function normalizeItem(mixed $item, string $locationId): array
    {
        if (! is_array($item) || ! is_string($item['id'] ?? null) || ! is_string($item['name'] ?? null)
            || ! is_numeric($item['price'] ?? null) || ! in_array($item['available'] ?? null, [true, false, 0, 1], true)) {
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
