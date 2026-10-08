@extends('layouts.heytruffle')

@section('title', 'Crear cuenta')

@section('content')
<section class="auth-hero">
    <div class="auth-intro">
        <p class="eyebrow">INTEGRACIÓN VITTLES POS</p>
        <h1>Empezá con <em>claridad.</em></h1>
        <p>Creá tu acceso local. Después vas directo a la página de inicio para explorar la integración.</p>
        <div class="auth-feature"><span class="orange-dot"></span> La primera cuenta puede abrir el laboratorio ADMIN.</div>
        <div class="auth-feature"><span class="orange-dot"></span> Las demás cuentas usan el flujo de órdenes.</div>
    </div>
    <div class="auth-card-wrap">
        <form method="post" action="{{ route('register.store') }}" class="auth-card">
            @csrf
            <p class="section-number">PRIMER PASO</p>
            <h2>Creá tu cuenta.</h2>
            <p class="auth-subtitle">Esta cuenta pertenece a la demo; no es una cuenta de Vittles.</p>
            @if ($errors->any())<div class="notice error" role="alert">{{ $errors->first() }}</div>@endif
            <label for="name">Nombre</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" maxlength="100" required autofocus>
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
            <p class="auth-hint">Al menos 8 caracteres, una letra y un número.</p>
            <label for="password_confirmation">Repetir contraseña</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
            <button type="submit" class="primary-button">Crear cuenta <span aria-hidden="true">→</span></button>
            <p class="auth-switch">¿Ya tenés una cuenta? <a href="{{ route('login') }}">Ingresar</a></p>
        </form>
    </div>
</section>
@endsection
