<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Integración POS') · heytruffle</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Gowun+Batang:wght@400;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/heytruffle.css') }}">
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a class="wordmark" href="{{ route('vittles.order') }}" aria-label="Inicio de la demo">heytruffle<span>*</span></a>
            <nav aria-label="Navegación principal">
                <a @class(['active' => request()->routeIs('vittles.order')]) href="{{ route('vittles.order') }}">Nueva orden</a>
                <a @class(['active' => request()->routeIs('vittles.locations')]) href="{{ route('vittles.locations') }}">Sedes y menús</a>
                <a @class(['active' => request()->routeIs('vittles.result')]) href="{{ route('vittles.result') }}">Resultado</a>
                @if (app()->environment('local') && in_array(parse_url((string) config('vittles.base_url'), PHP_URL_HOST), ['127.0.0.1', 'localhost'], true) && in_array(request()->ip(), ['127.0.0.1', '::1'], true))
                    <a @class(['active' => request()->routeIs('admin.*')]) href="{{ route('admin.index') }}">ADMIN</a>
                @endif
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <span>Demo local de integración con Vittles POS</span>
        <span>Diseño inspirado en <a href="https://heytruffle.ai/" target="_blank" rel="noopener noreferrer">heytruffle.ai</a> · No es un producto oficial</span>
    </footer>
    @stack('scripts')
</body>
</html>
