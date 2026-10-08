<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\DiagnosticLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    /** Inyecta el registro diagnóstico para seguir los accesos sin guardar contraseñas. */
    public function __construct(private readonly DiagnosticLog $diagnostics) {}

    /** Muestra el formulario local de ingreso; no necesita consultar a Vittles. */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /** Valida credenciales de usuario, renueva la sesión para evitar fijación y registra el resultado. */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            $this->diagnostics->event('warning', 'auth.login.failed', __METHOD__, ['reason_code' => 'invalid_credentials']);

            return back()->withErrors(['email' => 'Las credenciales no coinciden.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $this->diagnostics->event('info', 'auth.login.succeeded', __METHOD__, ['user_id' => Auth::id()]);

        return redirect()->route('vittles.order');
    }

    /** Muestra el alta de usuarios solo en la demo local para no exponer un registro público. */
    public function showRegister(Request $request): View
    {
        $this->onlyLocal($request);

        return view('auth.register');
    }

    /** Crea una cuenta web/CLI; la primera recibe ADMIN para habilitar el diagnóstico local. */
    public function register(Request $request): RedirectResponse
    {
        $this->onlyLocal($request);
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = DB::transaction(function () use ($data): User {
            return User::create([
                'name' => $data['name'],
                'email' => mb_strtolower($data['email']),
                'password' => $data['password'],
                'is_admin' => ! User::query()->exists(),
            ]);
        });

        Auth::login($user);
        $request->session()->regenerate();
        $this->diagnostics->event('info', 'auth.register.succeeded', __METHOD__, ['user_id' => $user->id]);

        return redirect()->route('vittles.order');
    }

    /** Cierra la sesión y regenera su token para invalidar credenciales de la sesión anterior. */
    public function logout(Request $request): RedirectResponse
    {
        $this->diagnostics->event('info', 'auth.logout', __METHOD__, ['user_id' => Auth::id()]);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /** Impide registrar usuarios desde un entorno o una dirección ajenos a esta demo local. */
    private function onlyLocal(Request $request): void
    {
        abort_unless(app()->environment('local') && in_array($request->ip(), ['127.0.0.1', '::1'], true), 404);
    }
}
