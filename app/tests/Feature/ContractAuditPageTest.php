<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContractAuditPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * El relato de auditoría solo está disponible dentro de la demo autenticada.
     * Un visitante debe pasar por el mismo acceso que el resto de las páginas.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/audit')->assertRedirect('/login');
    }

    /**
     * Recorre los cinco hallazgos y su procedencia sin contactar al mock.
     * Protege la distinción entre evidencia ya observada y una nueva prueba
     * que pudiera crear órdenes o alterar el estado durante una entrevista.
     */
    public function test_guide_explains_each_discrepancy_without_pos_requests(): void
    {
        Http::preventStrayRequests();
        $user = User::create([
            'name' => 'Demo',
            'email' => 'audit@example.com',
            'password' => 'secret123',
            'is_admin' => false,
        ]);
        $this->actingAs($user);

        $this->get('/audit')
            ->assertOk()
            ->assertSee('El token no dura lo documentado.')
            ->assertSee('Cómo llegó el agente al contrato real.')
            ->assertSee('Aisló el contexto faltante')
            ->assertSee('preflight.json')
            ->assertSee('Cómo lo encontró el agente auditor');
        $this->get('/audit?step=locations')->assertOk()->assertSee('páginas de 2, 2 y 1');
        $this->get('/audit?step=menus')->assertOk()->assertSee('menuItems');
        $this->get('/audit?step=order')->assertOk()->assertSee('REJECTED');
        $this->get('/audit?step=idempotency')->assertOk()->assertSee('No promete exactly once distribuido');
        $this->get('/audit?step=desconocido')->assertOk()->assertSee('El token no dura lo documentado.');

        Http::assertNothingSent();
    }

    /**
     * ADMIN reproduce el POST publicado para que la discrepancia sea visible
     * como 200/REJECTED, con el cuerpo del pedido y sin el header omitido en la guía.
     * El test verifica el request saliente y que la pantalla no lo llame creación.
     */
    public function test_admin_replays_documented_order_without_location_header(): void
    {
        $this->app->instance('env', 'local');
        $this->withoutMiddleware(ValidateCsrfToken::class);
        config()->set('vittles.base_url', 'http://127.0.0.1:8422');
        config()->set('vittles.client_id', 'test-id');
        config()->set('vittles.client_secret', 'test-secret');
        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'test-token', 'token_type' => 'bearer', 'expires' => 90]),
            '*/v1/orders' => Http::response(['status' => 'REJECTED', 'reason' => 'missing location context']),
        ]);
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-audit@example.com',
            'password' => 'secret123',
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->post('/admin/probe', ['probe' => 'order-documented'])
            ->assertRedirect('/admin')
            ->assertSessionHas('admin_probe_result.data.status', 'REJECTED');

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && str_ends_with($request->url(), '/v1/orders')
            && ! $request->hasHeader('X-Vittles-Location')
            && $request->data()['items'][0]['quantity'] === 2);
    }
}
