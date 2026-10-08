@extends('layouts.heytruffle')

@section('title', 'Ingresar')

@section('content')
<section class="auth-hero">
    <div class="auth-intro">
        <p class="eyebrow">INTEGRACIÓN VITTLES POS</p>
        <h1>Volvé a <em>tu espacio.</em></h1>
        <p>Accedé a la demo para consultar sedes y menús, crear órdenes y revisar el resultado.</p>
        <div class="auth-feature"><span class="orange-dot"></span> Una orden por vez, con un resultado claro.</div>
        <div class="auth-feature"><span class="orange-dot"></span> La conexión al POS queda del lado servidor.</div>
    </div>
    <div class="auth-card-wrap">
        <form method="post" action="{{ route('login.attempt') }}" class="auth-card">
            @csrf
            <p class="section-number">BIENVENIDO DE NUEVO</p>
            <h2>Ingresá a la demo.</h2>
            <p class="auth-subtitle">Usá la cuenta que creaste en esta instalación local.</p>
            @if ($errors->any())<div class="notice error" role="alert">{{ $errors->first() }}</div>@endif
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button type="submit" class="primary-button">Ingresar <span aria-hidden="true">→</span></button>
            @if (app()->environment('local') && in_array(request()->ip(), ['127.0.0.1', '::1'], true))
                <p class="auth-switch">¿Todavía no tenés cuenta? <a href="{{ route('register') }}">Crear cuenta</a></p>
            @endif
        </form>
    </div>
</section>
@endsection
