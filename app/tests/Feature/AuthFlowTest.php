<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Comprueba que un visitante no pueda ver la demo ni crear pedidos.
     * Protege el límite entre las rutas públicas de acceso y las rutas autenticadas.
     */
    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
        $this->post('/orders', [])->assertRedirect('/login');
    }

    /**
     * Registra la primera cuenta en modo local y verifica normalización del email,
     * hash de contraseña, sesión iniciada y acceso ADMIN reservado a esa cuenta.
     */
    public function test_first_local_registration_reaches_home_and_has_admin_access(): void
    {
        app()->instance('env', 'local');
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->post('/register', [
            'name' => 'Giovanni',
            'email' => 'Giovanni@Example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect('/');

        $user = User::query()->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('giovanni@example.com', $user->email);
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->get('/admin')->assertOk();
    }

    /**
     * La segunda cuenta debe quedar sin rol ADMIN; también comprueba que logout
     * invalida la sesión y que las mismas credenciales permiten volver a ingresar.
     */
    public function test_later_user_cannot_see_admin_and_can_log_out_and_back_in(): void
    {
        app()->instance('env', 'local');
        $this->withoutMiddleware(ValidateCsrfToken::class);
        User::create(['name' => 'First', 'email' => 'first@example.com', 'password' => 'secret123', 'is_admin' => true]);

        $this->post('/register', [
            'name' => 'Second',
            'email' => 'second@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect('/');

        $this->assertFalse(auth()->user()->is_admin);
        $this->get('/admin')->assertForbidden();
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->post('/login', ['email' => 'second@example.com', 'password' => 'secret123'])->assertRedirect('/');
        $this->assertAuthenticated();
    }

    /**
     * Fuera del entorno local, tanto el formulario como el POST de registro dan 404.
     * Evita convertir el alta de esta demo en una función pública accidental.
     */
    public function test_registration_is_closed_outside_local_environment(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }
}
