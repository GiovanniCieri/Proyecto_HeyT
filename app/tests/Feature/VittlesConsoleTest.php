<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VittlesConsoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_preview_the_exact_exercise_command_and_exit(): void
    {
        $this->artisan('vittles:console')
            ->expectsChoice('Acceso', 'Ver comando del ejercicio', ['Ingresar', 'Registrarse', 'README', 'Ver comando del ejercicio', 'Salir'])
            ->expectsOutputToContain('php artisan vittles:order loc_1001 "Buffalo Wings (12)"')
            ->expectsChoice('Acceso', 'Salir', ['Ingresar', 'Registrarse', 'README', 'Ver comando del ejercicio', 'Salir'])
            ->assertSuccessful();
    }

    public function test_local_registration_creates_a_web_compatible_account_and_exposes_admin(): void
    {
        app()->instance('env', 'local');
        $this->artisan('vittles:console')
            ->expectsChoice('Acceso', 'Registrarse', ['Ingresar', 'Registrarse', 'README', 'Ver comando del ejercicio', 'Salir'])
            ->expectsQuestion('Nombre', 'Giovanni')
            ->expectsQuestion('Email', 'gio@example.com')
            ->expectsQuestion('Contraseña', 'secret123')
            ->expectsQuestion('Repetí la contraseña', 'secret123')
            ->expectsOutputToContain('Cuenta creada')
            ->expectsChoice('¿Qué querés hacer?', 'Salir', [
                'Nueva orden', 'Sedes y menús', 'Pedidos confirmados', 'Ver pedido en Vittles', 'README',
                'Ejecutar comando del ejercicio', 'ADMIN · Diagnóstico', 'Cerrar sesión', 'Salir',
            ])
            ->assertSuccessful();

        $user = User::query()->firstOrFail();
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_non_admin_login_cannot_see_diagnostic_menu(): void
    {
        app()->instance('env', 'local');
        User::create(['name' => 'Demo', 'email' => 'demo@example.com', 'password' => 'secret123', 'is_admin' => false]);
        $this->artisan('vittles:console')
            ->expectsChoice('Acceso', 'Ingresar', ['Ingresar', 'Registrarse', 'README', 'Ver comando del ejercicio', 'Salir'])
            ->expectsQuestion('Email', 'demo@example.com')
            ->expectsQuestion('Contraseña', 'secret123')
            ->expectsChoice('¿Qué querés hacer?', 'Salir', [
                'Nueva orden', 'Sedes y menús', 'Pedidos confirmados', 'Ver pedido en Vittles', 'README',
                'Ejecutar comando del ejercicio', 'Cerrar sesión', 'Salir',
            ])
            ->assertSuccessful();
    }

    public function test_interactive_menu_creates_one_multi_product_order(): void
    {
        app()->instance('env', 'local');
        $user = User::create(['name' => 'Demo', 'email' => 'demo@example.com', 'password' => 'secret123', 'is_admin' => false]);
        config()->set('vittles.base_url', 'http://vittles.test');
        config()->set('vittles.client_id', 'test-id');
        config()->set('vittles.client_secret', 'test-secret');
        $posts = 0;
        Http::fake(function (Request $request) use (&$posts, $user) {
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
                $posts++;
                $this->assertSame('heyt-'.substr(hash('sha256', json_encode([
                    'multi-v1', 'web-user-'.$user->id.'-demo', 'loc_1001', [['itm_88', 2], ['itm_91', 1]],
                ], JSON_UNESCAPED_UNICODE)), 0, 32), $request->data()['client_ref']);

                return Http::response([
                    'id' => 'ord_console', 'status' => 'ACCEPTED', 'total' => 39.25,
                    'client_ref' => $request->data()['client_ref'],
                ], 201);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });

        $guest = ['Ingresar', 'Registrarse', 'README', 'Ver comando del ejercicio', 'Salir'];
        $main = ['Nueva orden', 'Sedes y menús', 'Pedidos confirmados', 'Ver pedido en Vittles', 'README', 'Ejecutar comando del ejercicio', 'Cerrar sesión', 'Salir'];
        $products = ['itm_88 · Buffalo Wings (12)', 'itm_91 · Loaded Fries', 'Finalizar selección', 'Cancelar'];
        $this->artisan('vittles:console')
            ->expectsChoice('Acceso', 'Ingresar', $guest)
            ->expectsQuestion('Email', 'demo@example.com')
            ->expectsQuestion('Contraseña', 'secret123')
            ->expectsChoice('¿Qué querés hacer?', 'Nueva orden', $main)
            ->expectsChoice('Location de destino', 'loc_1001 · Midtown', ['loc_1001 · Midtown', 'Cancelar'])
            ->expectsChoice('Agregar o cambiar cantidad', $products[0], $products)
            ->expectsQuestion('Cantidad de Buffalo Wings (12) (1–20; 0 para quitar)', '2')
            ->expectsChoice('Agregar o cambiar cantidad', $products[1], $products)
            ->expectsQuestion('Cantidad de Loaded Fries (1–20; 0 para quitar)', '1')
            ->expectsChoice('Agregar o cambiar cantidad', 'Finalizar selección', $products)
            ->expectsQuestion('Identificador de compra', 'demo')
            ->expectsConfirmation('¿Crear o recuperar esta orden?', 'yes')
            ->expectsOutputToContain('ord_console')
            ->expectsChoice('¿Qué querés hacer?', 'Salir', $main)
            ->assertSuccessful();

        $this->assertSame(1, $posts);
    }

    public function test_admin_auth_probe_redacts_the_pos_token(): void
    {
        app()->instance('env', 'local');
        User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'secret123', 'is_admin' => true]);
        config()->set('vittles.base_url', 'http://127.0.0.1:8422');
        config()->set('vittles.client_id', 'test-id');
        config()->set('vittles.client_secret', 'test-secret');
        Http::fake(['*/oauth/token' => Http::response(['access_token' => 'private-test-token', 'token_type' => 'bearer', 'expires' => 90])]);

        $guest = ['Ingresar', 'Registrarse', 'README', 'Ver comando del ejercicio', 'Salir'];
        $main = ['Nueva orden', 'Sedes y menús', 'Pedidos confirmados', 'Ver pedido en Vittles', 'README', 'Ejecutar comando del ejercicio', 'ADMIN · Diagnóstico', 'Cerrar sesión', 'Salir'];
        $admin = ['Ver trazas', 'Probar autenticación', 'Consultar página de locations', 'Recorrer catálogo completo', 'Consultar menú', 'Buscar por client_ref', 'Leer orden por ID', 'Probar rechazo', 'Crear o recuperar orden de prueba', 'Volver'];
        $this->artisan('vittles:console')
            ->expectsChoice('Acceso', 'Ingresar', $guest)
            ->expectsQuestion('Email', 'admin@example.com')
            ->expectsQuestion('Contraseña', 'secret123')
            ->expectsChoice('¿Qué querés hacer?', 'ADMIN · Diagnóstico', $main)
            ->expectsChoice('Laboratorio de API', 'Probar autenticación', $admin)
            ->expectsOutputToContain('[REDACTED]')
            ->doesntExpectOutputToContain('private-test-token')
            ->expectsChoice('Laboratorio de API', 'Volver', $admin)
            ->expectsChoice('¿Qué querés hacer?', 'Salir', $main)
            ->assertSuccessful();
    }

    public function test_menu_button_runs_the_original_fixed_quantity_command(): void
    {
        app()->instance('env', 'local');
        User::create(['name' => 'Demo', 'email' => 'demo@example.com', 'password' => 'secret123', 'is_admin' => false]);
        config()->set('vittles.base_url', 'http://vittles.test');
        config()->set('vittles.client_id', 'test-id');
        config()->set('vittles.client_secret', 'test-secret');
        $posted = null;
        Http::fake(function (Request $request) use (&$posted) {
            $path = parse_url($request->url(), PHP_URL_PATH);

            return match (true) {
                $path === '/oauth/token' => Http::response(['access_token' => 'test-token', 'expires' => 90]),
                $path === '/v1/locations' => Http::response(['data' => [['id' => 'loc_1001', 'name' => 'Midtown', 'active' => true]]]),
                $path === '/v1/locations/loc_1001/menu' => Http::response(['menuItems' => [['id' => 'itm_88', 'name' => 'Buffalo Wings (12)', 'price' => 15.5, 'available' => true]]]),
                $path === '/v1/orders' && $request->method() === 'GET' => Http::response(['data' => []]),
                $path === '/v1/orders' && $request->method() === 'POST' => (function () use ($request, &$posted) {
                    $posted = $request->data()['items'];

                    return Http::response(['id' => 'ord_direct', 'status' => 'ACCEPTED', 'total' => 31, 'client_ref' => $request->data()['client_ref']], 201);
                })(),
                default => Http::response(['error' => 'unexpected'], 500),
            };
        });

        $guest = ['Ingresar', 'Registrarse', 'README', 'Ver comando del ejercicio', 'Salir'];
        $main = ['Nueva orden', 'Sedes y menús', 'Pedidos confirmados', 'Ver pedido en Vittles', 'README', 'Ejecutar comando del ejercicio', 'Cerrar sesión', 'Salir'];
        $this->artisan('vittles:console')
            ->expectsChoice('Acceso', 'Ingresar', $guest)
            ->expectsQuestion('Email', 'demo@example.com')
            ->expectsQuestion('Contraseña', 'secret123')
            ->expectsChoice('¿Qué querés hacer?', 'Ejecutar comando del ejercicio', $main)
            ->expectsOutputToContain('php artisan vittles:order loc_1001 "Buffalo Wings (12)"')
            ->expectsConfirmation('¿Ejecutarlo desde este menú?', 'yes')
            ->expectsOutputToContain('ord_direct')
            ->expectsChoice('¿Qué querés hacer?', 'Salir', $main)
            ->assertSuccessful();

        $this->assertSame([['item_id' => 'itm_88', 'quantity' => 2]], $posted);
    }
}
