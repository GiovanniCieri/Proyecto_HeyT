<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Cómo lo encontró el agente auditor');
        $this->get('/audit?step=locations')->assertOk()->assertSee('páginas de 2, 2 y 1');
        $this->get('/audit?step=menus')->assertOk()->assertSee('menuItems');
        $this->get('/audit?step=order')->assertOk()->assertSee('REJECTED');
        $this->get('/audit?step=idempotency')->assertOk()->assertSee('No promete exactly once distribuido');
        $this->get('/audit?step=desconocido')->assertOk()->assertSee('El token no dura lo documentado.');

        Http::assertNothingSent();
    }
}
