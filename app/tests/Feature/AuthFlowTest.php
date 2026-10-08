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

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
        $this->post('/orders', [])->assertRedirect('/login');
    }

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

    public function test_registration_is_closed_outside_local_environment(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }
}
