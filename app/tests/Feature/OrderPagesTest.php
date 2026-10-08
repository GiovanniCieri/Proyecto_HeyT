<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Vittles\ConfirmedOrderStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_orders_are_grouped_and_filterable_by_location_without_calling_vittles(): void
    {
        Http::preventStrayRequests();
        $store = app(ConfirmedOrderStore::class);
        foreach (['loc_1001', 'loc_1002'] as $id) {
            $store->record([
                'client_ref' => 'heyt-'.$id,
                'location' => ['id' => $id, 'name' => 'Location '.$id],
                'item' => ['id' => 'itm_88', 'name' => 'Buffalo Wings (12)'],
                'quantity' => 2,
                'order' => ['id' => 'ord_'.$id, 'total' => '31.00'],
            ]);
        }
        $store->record([
            'client_ref' => 'heyt-loc_1001',
            'location' => ['id' => 'loc_1001', 'name' => 'Location loc_1001'],
            'item' => ['id' => 'itm_88', 'name' => 'Buffalo Wings (12)'],
            'quantity' => 2,
            'order' => ['id' => 'ord_loc_1001', 'total' => '31.00'],
        ]);

        $this->assertSame(2, DB::table('confirmed_orders')->count());
        $this->actingAs($this->user())
            ->get('/orders?location=loc_1001')
            ->assertOk()
            ->assertSee('ord_loc_1001')
            ->assertDontSee('ord_loc_1002');
        Http::assertNothingSent();
    }

    public function test_readme_explains_fixed_cli_quantity_and_deliberate_exclusions(): void
    {
        $this->actingAs($this->user())
            ->get('/readme')
            ->assertOk()
            ->assertSee('el comando del ejercicio siempre envía 2')
            ->assertSee('FUERA DE ALCANCE A PROPÓSITO');
    }

    public function test_web_quantity_is_sent_to_vittles_and_recorded_with_confirmed_total(): void
    {
        config()->set('vittles.base_url', 'http://vittles.test');
        config()->set('vittles.client_id', 'test-id');
        config()->set('vittles.client_secret', 'test-secret');
        $postedQuantity = null;
        Http::fake(function (Request $request) use (&$postedQuantity) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if ($path === '/oauth/token') {
                return Http::response(['access_token' => 'test-token', 'expires' => 90]);
            }
            if ($path === '/v1/locations') {
                return Http::response(['data' => [['id' => 'loc_1001', 'name' => 'Midtown', 'active' => true]]]);
            }
            if ($path === '/v1/locations/loc_1001/menu') {
                return Http::response(['menuItems' => [['id' => 'itm_88', 'name' => 'Buffalo Wings (12)', 'price' => 15.5, 'available' => true]]]);
            }
            if ($path === '/v1/orders' && $request->method() === 'GET') {
                return Http::response(['data' => []]);
            }
            if ($path === '/v1/orders' && $request->method() === 'POST') {
                $postedQuantity = $request->data()['items'][0]['quantity'];

                return Http::response([
                    'id' => 'ord_web_1', 'status' => 'ACCEPTED', 'total' => 46.5,
                    'client_ref' => $request->data()['client_ref'],
                ], 201);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });

        $this->actingAs($this->user())
            ->post('/orders', [
                'location' => 'loc_1001', 'item' => 'Buffalo Wings (12)',
                'quantity' => 3, 'request_key' => 'compra-web-1',
            ])
            ->assertRedirect('/result');

        $this->assertSame(3, $postedQuantity);
        $saved = DB::table('confirmed_orders')->first();
        $this->assertSame(3, $saved->quantity);
        $this->assertSame('46.5', (string) (float) $saved->total);
        $this->get('/result')->assertOk()->assertSee('ord_web_1')->assertSee('× 3');
    }

    public function test_web_rejects_out_of_range_quantity_before_any_pos_request(): void
    {
        Http::preventStrayRequests();
        $this->actingAs($this->user())
            ->post('/orders', [
                'location' => 'loc_1001', 'item' => 'Buffalo Wings (12)', 'quantity' => 0,
            ])
            ->assertSessionHasErrors('quantity');
        Http::assertNothingSent();
    }

    private function user(): User
    {
        return User::create([
            'name' => 'Demo',
            'email' => 'demo@example.com',
            'password' => 'secret123',
            'is_admin' => false,
        ]);
    }
}
