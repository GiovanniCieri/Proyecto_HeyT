<?php

namespace App\Http\Controllers;

use App\Services\Vittles\CatalogService;
use App\Services\Vittles\OrderService;
use App\Services\Vittles\TraceStore;
use App\Services\Vittles\VittlesClient;
use App\Services\Vittles\VittlesException;
use App\Support\DiagnosticLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class AdminController extends Controller
{
    public function __construct(private readonly DiagnosticLog $diagnostics) {}

    public function index(Request $request, TraceStore $traces): View
    {
        $this->onlyLocalMock();
        $recent = $traces->recent();
        $filter = in_array($request->query('filter'), ['all', 'errors', 'post'], true) ? $request->query('filter') : 'all';
        $searchId = is_string($request->query('id')) && preg_match('/^[a-f0-9]{16}$/', $request->query('id')) ? $request->query('id') : '';
        $visible = array_values(array_filter($recent, function (array $entry) use ($filter, $searchId) {
            if ($searchId !== '' && ($entry['correlation_id'] ?? null) !== $searchId) {
                return false;
            }
            if ($filter === 'post') {
                return $entry['method'] === 'POST';
            }
            if ($filter === 'errors') {
                return $this->isError($entry);
            }

            return true;
        }));

        return view('admin.index', [
            'traces' => $visible,
            'filter' => $filter,
            'searchId' => $searchId,
            'count' => count($recent),
            'errorCount' => count(array_filter($recent, fn (array $entry) => $this->isError($entry))),
        ]);
    }

    public function probe(Request $request, VittlesClient $client, CatalogService $catalog, OrderService $orders): RedirectResponse
    {
        $this->onlyLocalMock();
        $kind = Validator::make($request->all(), [
            'probe' => ['required', Rule::in(['auth', 'locations', 'catalog', 'menu', 'order-search', 'order-detail', 'order-rejected', 'order-create'])],
        ])->validate()['probe'];
        $this->diagnostics->event('info', 'admin.probe.started', __METHOD__, ['probe' => $kind]);

        try {
            $result = match ($kind) {
                'auth' => $client->inspectAuthentication(),
                'locations' => $client->get('/v1/locations', $request->filled('cursor') ? ['cursor' => $request->validate(['cursor' => ['string', 'max:30']])['cursor']] : []),
                'catalog' => $this->catalogSummary($catalog->load()),
                'menu' => $client->get('/v1/locations/'.rawurlencode($request->validate(['location_id' => ['required', 'string', 'max:80']])['location_id']).'/menu'),
                'order-search' => $client->get('/v1/orders', ['client_ref' => $request->validate(['client_ref' => ['required', 'string', 'max:100']])['client_ref']]),
                'order-detail' => $client->get('/v1/orders/'.rawurlencode($request->validate(['order_id' => ['required', 'string', 'max:80']])['order_id'])),
                'order-rejected' => $client->probeRejectedOrder(),
                'order-create' => $this->createOrder($request, $orders),
            };
        } catch (InvalidArgumentException|VittlesException $e) {
            $this->diagnostics->event('warning', 'admin.probe.failed', __METHOD__, ['probe' => $kind, 'error_type' => $e::class, 'http_status' => $e instanceof VittlesException ? $e->httpStatus : null]);

            return redirect()->route('admin.index')->with('admin_probe_error', $e->getMessage());
        }

        $this->diagnostics->event('info', 'admin.probe.finished', __METHOD__, ['probe' => $kind]);

        return redirect()->route('admin.index')->with('admin_probe_result', ['probe' => $kind, 'data' => $result]);
    }

    private function createOrder(Request $request, OrderService $orders): array
    {
        $input = $request->validate([
            'location_id' => ['required', 'string', 'max:80'],
            'item_name' => ['required', 'string', 'max:200'],
            'request_key' => ['required', 'string', 'max:80'],
        ]);
        $result = $orders->place($input['location_id'], $input['item_name'], $input['request_key']);

        return [
            'status' => $result['status'],
            'client_ref' => $result['client_ref'],
            'order' => $result['order'] ?? null,
            'message' => $result['message'] ?? null,
            'locations_read' => count($result['catalog']['locations']),
            'menus_read' => count($result['catalog']['menus']),
        ];
    }

    private function catalogSummary(array $catalog): array
    {
        return [
            'locations' => $catalog['locations'],
            'menus' => array_map(fn (array $menu) => [
                'status' => $menu['status'],
                'items_count' => count($menu['items']),
                'message' => $menu['message'] ?? null,
            ], $catalog['menus']),
        ];
    }

    private function isError(array $entry): bool
    {
        return ($entry['transport_error'] ?? null) !== null
            || ($entry['status'] ?? 0) >= 400
            || ($entry['response_body']['status'] ?? null) === 'REJECTED';
    }

    private function onlyLocalMock(): void
    {
        $host = parse_url((string) config('vittles.base_url'), PHP_URL_HOST);
        abort_unless(app()->environment('local')
            && in_array($host, ['127.0.0.1', 'localhost'], true)
            && in_array(request()->ip(), ['127.0.0.1', '::1'], true), 404);
        abort_unless(request()->user()?->is_admin, 403);
    }
}
