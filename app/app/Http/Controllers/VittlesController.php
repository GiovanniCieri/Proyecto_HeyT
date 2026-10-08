<?php

namespace App\Http\Controllers;

use App\Services\Vittles\CatalogService;
use App\Services\Vittles\ConfirmedOrderStore;
use App\Services\Vittles\OrderService;
use App\Services\Vittles\VittlesException;
use App\Support\DiagnosticLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class VittlesController extends Controller
{
    public function __construct(private readonly DiagnosticLog $diagnostics) {}

    public function index(CatalogService $catalog): View
    {
        return view('vittles.order', $this->catalogData($catalog));
    }

    public function locations(CatalogService $catalog): View
    {
        return view('vittles.locations', $this->catalogData($catalog));
    }

    public function orders(Request $request, ConfirmedOrderStore $confirmedOrders): View
    {
        $input = $request->validate(['location' => ['nullable', 'string', 'max:80']]);
        $locationId = $input['location'] ?? null;
        $locations = $confirmedOrders->locations();
        $orders = $confirmedOrders->recent($locationId);
        $this->diagnostics->event('info', 'web.orders.listed', __METHOD__, [
            'location_id' => $locationId,
            'items_count' => count($orders),
        ]);

        return view('vittles.orders', compact('locations', 'orders', 'locationId'));
    }

    public function readme(): View
    {
        return view('vittles.readme');
    }

    public function orderDetail(string $clientRef, ConfirmedOrderStore $confirmedOrders): View
    {
        $order = $confirmedOrders->findByReference($clientRef);
        abort_if($order === null, 404);

        return view('vittles.order-detail', ['order' => $order]);
    }

    public function place(Request $request, OrderService $orders): RedirectResponse
    {
        $input = $request->validate([
            'location' => ['required', 'string', 'max:80'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.item_id' => ['required', 'string', 'max:80'],
            'items.*.quantity' => ['required', 'integer', 'between:0,20'],
            'request_key' => ['nullable', 'string', 'max:80'],
        ]);
        $lines = array_values(array_filter(array_map(fn (array $line) => [
            'item_id' => $line['item_id'], 'quantity' => (int) $line['quantity'],
        ], $input['items']), fn (array $line) => $line['quantity'] > 0));
        if ($lines === []) {
            throw ValidationException::withMessages(['items' => 'Elegí al menos un producto con cantidad mayor que cero.']);
        }

        try {
            $webKey = 'web-user-'.$request->user()->getAuthIdentifier().'-'.($input['request_key'] ?? 'demo');
            $result = $orders->placeMany($input['location'], $lines, $webKey);
        } catch (InvalidArgumentException|VittlesException $e) {
            $this->diagnostics->event('warning', 'web.order.failed', __METHOD__, ['error_type' => $e::class, 'http_status' => $e instanceof VittlesException ? $e->httpStatus : null]);

            return back()->withInput()->with('operation_error', $e->getMessage());
        }

        $this->diagnostics->event('info', 'web.order.finished', __METHOD__, ['result' => $result['status'], 'order_id' => $result['order']['id'] ?? null]);

        return redirect()->route('vittles.result')->with('vittles_last_result', $result);
    }

    public function result(Request $request): View|RedirectResponse
    {
        $result = $request->session()->get('vittles_last_result');
        if (! is_array($result)) {
            $this->diagnostics->event('info', 'web.result.empty', __METHOD__);

            return redirect()->route('vittles.orders');
        }

        return view('vittles.result', ['result' => $result]);
    }

    private function catalogData(CatalogService $catalog): array
    {
        try {
            $cache = Cache::store('file');
            $hit = $cache->has('vittles-web-catalog');
            $this->diagnostics->event('info', 'web.catalog.requested', __METHOD__, ['cache_hit' => $hit]);

            return [
                'catalog' => $cache->remember('vittles-web-catalog', 10, fn () => $catalog->load()),
                'catalogError' => null,
            ];
        } catch (VittlesException $e) {
            $this->diagnostics->event('warning', 'web.catalog.failed', __METHOD__, ['error_type' => $e::class, 'http_status' => $e->httpStatus]);

            return ['catalog' => ['locations' => [], 'menus' => []], 'catalogError' => $e->getMessage()];
        }
    }
}
