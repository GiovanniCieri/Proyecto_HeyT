<?php

namespace Tests\Feature;

use App\Services\Vittles\OrderService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VittlesIntegrationTest extends TestCase
{
    public function test_second_execution_reuses_the_same_order_without_a_second_post(): void
    {
        $posts = 0;
        $reference = null;
        $this->fakeVittles($posts, $reference);

        $service = app(OrderService::class);
        $first = $service->place('loc_1001', 'Buffalo Wings (12)');
        $second = $service->place('loc_1001', 'Buffalo Wings (12)');

        $this->assertSame('CREATED', $first['status']);
        $this->assertSame('EXISTING', $second['status']);
        $this->assertSame('ord_5501', $first['order']['id']);
        $this->assertSame($first['order'], $second['order']);
        $this->assertSame(1, $posts);
        $this->assertCount(5, $first['catalog']['locations']);
        $this->assertCount(5, $first['catalog']['menus']);
        $this->assertSame('forbidden', $first['catalog']['menus']['loc_1004']['status']);
    }

    public function test_unavailable_product_never_reaches_order_post(): void
    {
        $posts = 0;
        $reference = null;
        $this->fakeVittles($posts, $reference);

        $this->expectExceptionMessage('no está disponible');
        try {
            app(OrderService::class)->place('loc_1005', 'Buffalo Wings (12)');
        } finally {
            $this->assertSame(0, $posts);
        }
    }

    public function test_http_200_rejected_is_not_reported_as_created(): void
    {
        $posts = 0;
        $reference = null;
        $this->fakeVittles($posts, $reference, true);

        $result = app(OrderService::class)->place('loc_1001', 'Buffalo Wings (12)', 'rejected-test');

        $this->assertSame('REJECTED', $result['status']);
        $this->assertArrayNotHasKey('order', $result);
        $this->assertSame(1, $posts);
    }

    public function test_unreadable_post_response_is_reconciled_without_resending(): void
    {
        $posts = 0;
        $reference = null;
        $this->fakeVittles($posts, $reference, malformedPost: true);

        $result = app(OrderService::class)->place('loc_1001', 'Buffalo Wings (12)', 'uncertain-test');

        $this->assertSame('RECOVERED', $result['status']);
        $this->assertSame('ord_5501', $result['order']['id']);
        $this->assertSame(1, $posts);
    }

    public function test_quantity_changes_the_purchase_reference_while_cli_default_remains_two(): void
    {
        $posts = 0;
        $reference = null;
        $this->fakeVittles($posts, $reference);

        $service = app(OrderService::class);
        $two = $service->place('loc_1001', 'Buffalo Wings (12)');
        $three = $service->place('loc_1001', 'Buffalo Wings (12)', 'demo', 3);

        $this->assertSame(2, $two['quantity']);
        $this->assertSame(3, $three['quantity']);
        $this->assertNotSame($two['client_ref'], $three['client_ref']);
        $this->assertSame(2, $posts);
    }

    private function fakeVittles(int &$posts, ?string &$reference, bool $reject = false, bool $malformedPost = false): void
    {
        config()->set('vittles.base_url', 'http://vittles.test');
        config()->set('vittles.client_id', 'test-id');
        config()->set('vittles.client_secret', 'test-secret');

        Http::fake(function (Request $request) use (&$posts, &$reference, $reject, $malformedPost) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            if ($path === '/oauth/token') {
                return Http::response(['access_token' => 'token', 'expires' => 90], 200);
            }
            if ($path === '/v1/locations') {
                $locations = array_map(fn (int $n) => [
                    'id' => 'loc_100'.$n, 'name' => 'Location '.$n, 'active' => $n !== 4,
                ], range(1, 5));
                $cursor = (int) ($query['cursor'] ?? 0);
                $response = ['data' => array_slice($locations, $cursor, 2)];
                if ($cursor + 2 < 5) {
                    $response['next_cursor'] = (string) ($cursor + 2);
                }

                return Http::response($response, 200);
            }
            if (preg_match('~^/v1/locations/(loc_100[1-5])/menu$~', $path, $matches)) {
                if ($matches[1] === 'loc_1004') {
                    return Http::response(['error' => 'forbidden'], 403);
                }

                return Http::response(['menuItems' => [[
                    'id' => 'itm_88', 'name' => 'Buffalo Wings (12)', 'price' => '15.50',
                    'available' => $matches[1] === 'loc_1005' ? 0 : 1,
                ]]], 200);
            }
            if ($path === '/v1/orders' && $request->method() === 'GET') {
                return Http::response(['data' => $reference && ($query['client_ref'] ?? null) === $reference
                    ? [['id' => 'ord_5501', 'status' => 'ACCEPTED', 'total' => 31, 'client_ref' => $reference]]
                    : []], 200);
            }
            if ($path === '/v1/orders' && $request->method() === 'POST') {
                $posts++;
                $reference = $request->data()['client_ref'];
                if ($reject) {
                    return Http::response(['status' => 'REJECTED', 'reason' => 'not allowed'], 200);
                }
                if ($malformedPost) {
                    return Http::response([], 201);
                }

                return Http::response(['id' => 'ord_5501', 'status' => 'ACCEPTED', 'total' => 15.5 * $request->data()['items'][0]['quantity'], 'client_ref' => $reference], 201);
            }

            return Http::response(['error' => 'unexpected request'], 500);
        });
    }
}
