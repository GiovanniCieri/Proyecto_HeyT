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

    /**
     * Crea dos confirmaciones locales y repite una referencia para probar el upsert.
     * El filtro muestra solo la sede pedida y no hace ninguna llamada a Vittles.
     */
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

    /**
     * Asegura que la página README diferencie el comando obligatorio de la demo web
     * y declare las exclusiones deliberadas requeridas por el enunciado.
     */
    public function test_readme_explains_fixed_cli_quantity_and_deliberate_exclusions(): void
    {
        $this->actingAs($this->user())
            ->get('/readme')
            ->assertOk()
            ->assertSee('el comando del ejercicio siempre envía 2')
            ->assertSee('FUERA DE ALCANCE A PROPÓSITO');
    }

    /**
     * Simula el POS y crea una compra web de dos líneas con cantidades distintas.
     * Comprueba payload, total confirmado, historial, resultado temporal y detalle.
     */
    public function test_web_sends_two_products_and_records_the_confirmed_total(): void
    {
        config()->set('vittles.base_url', 'http://vittles.test');
        config()->set('vittles.client_id', 'test-id');
        config()->set('vittles.client_secret', 'test-secret');
        $postedItems = null;
        Http::fake(function (Request $request) use (&$postedItems) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if ($path === '/oauth/token') {
                return Http::response(['access_token' => 'test-token', 'expires' => 90]);
            }
            if ($path === '/v1/locations') {
                return Http::response(['data' => [['id' => 'loc_1001', 'name' => 'Midtown', 'active' => true]]]);
            }
            if ($path === '/v1/locations/loc_1001/menu') {
                return Http::response(['menuItems' => [
                    ['id' => 'itm_88', 'name' => 'Buffalo Wings (12)', 'price' => 15.5, 'available' => true],
                    ['id' => 'itm_91', 'name' => 'Loaded Fries', 'price' => 8.25, 'available' => true],
                ]]);
            }
            if ($path === '/v1/orders' && $request->method() === 'GET') {
                return Http::response(['data' => []]);
            }
            if ($path === '/v1/orders' && $request->method() === 'POST') {
                $postedItems = $request->data()['items'];

                return Http::response([
                    'id' => 'ord_web_1', 'status' => 'ACCEPTED', 'total' => 63,
                    'client_ref' => $request->data()['client_ref'],
                ], 201);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });

        $this->actingAs($this->user())
            ->post('/orders', [
                'location' => 'loc_1001', 'items' => [
                    ['item_id' => 'itm_88', 'quantity' => 3],
                    ['item_id' => 'itm_91', 'quantity' => 2],
                ], 'request_key' => 'compra-web-1',
            ])
            ->assertRedirect('/result');

        $this->assertSame([
            ['item_id' => 'itm_88', 'quantity' => 3],
            ['item_id' => 'itm_91', 'quantity' => 2],
        ], $postedItems);
        $saved = DB::table('confirmed_orders')->first();
        $this->assertSame(5, $saved->quantity);
        $this->assertCount(2, json_decode($saved->items_json, true));
        $this->assertSame('63', (string) (float) $saved->total);
        $this->get('/result')->assertOk()->assertSee('ord_web_1')->assertSee('× 3')->assertSee('× 2');
        $this->get('/result')->assertRedirect('/orders');
        $this->get('/orders/'.$saved->client_ref)->assertOk()->assertSee('Loaded Fries');
    }

    /**
     * Un formulario sin cantidades positivas falla antes de contactar al POS.
     * Previene aceptar el comportamiento defectuoso del mock, que convierte 0 en 1.
     */
    public function test_web_rejects_out_of_range_quantity_before_any_pos_request(): void
    {
        Http::preventStrayRequests();
        $this->actingAs($this->user())
            ->post('/orders', [
                'location' => 'loc_1001', 'items' => [['item_id' => 'itm_88', 'quantity' => 0]],
            ])
            ->assertSessionHasErrors('items');
        Http::assertNothingSent();
    }

    /**
     * El comando de historial debe leer SQLite y funcionar aunque Vittles esté caído.
     * La prohibición de requests salientes detecta consultas remotas accidentales.
     */
    public function test_orders_command_lists_local_history_without_contacting_pos(): void
    {
        Http::preventStrayRequests();
        app(ConfirmedOrderStore::class)->record([
            'client_ref' => 'heyt-local-command',
            'location' => ['id' => 'loc_1001', 'name' => 'Midtown'],
            'item' => ['id' => 'itm_88', 'name' => 'Buffalo Wings (12)'],
            'quantity' => 2,
            'order' => ['id' => 'ord_local', 'total' => '31.00'],
        ]);

        $this->artisan('vittles:orders', ['location' => 'loc_1001'])
            ->expectsOutputToContain('historial local')
            ->assertSuccessful();
        Http::assertNothingSent();
    }

    /**
     * El comando de consulta por ID sí debe leer Vittles en vivo y mostrar ID y total.
     * Distingue esta consulta del historial local, que no enumera todo el POS.
     */
    public function test_show_command_reads_one_order_directly_from_pos(): void
    {
        config()->set('vittles.base_url', 'http://vittles.test');
        config()->set('vittles.client_id', 'test-id');
        config()->set('vittles.client_secret', 'test-secret');
        Http::fake(function (Request $request) {
            if (str_ends_with($request->url(), '/oauth/token')) {
                return Http::response(['access_token' => 'test-token', 'expires' => 90]);
            }
            if (str_ends_with($request->url(), '/v1/orders/ord_55')) {
                return Http::response(['id' => 'ord_55', 'status' => 'ACCEPTED', 'total' => 39.25, 'client_ref' => 'heyt-test']);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });

        $this->artisan('vittles:show', ['order_id' => 'ord_55'])
            ->expectsOutputToContain('ord_55')
            ->expectsOutputToContain('$39.25')
            ->assertSuccessful();
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v1/orders/ord_55'));
    }

    /** Fabrica una cuenta común para probar las páginas protegidas sin repetir el alta. */
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
