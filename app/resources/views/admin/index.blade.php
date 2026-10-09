@extends('layouts.heytruffle')

@section('title', 'ADMIN · Diagnóstico')

@section('content')
<section class="hero admin-hero">
    <p class="eyebrow">INTEGRACIÓN VITTLES POS · ADMIN</p>
    <h1>Lo que Vittles <em>realmente devuelve.</em></h1>
    <p class="hero-copy">Probá cada endpoint y revisá solicitudes, respuestas, tiempos, reintentos y errores. Esta sección funciona solo con el mock local.</p>
    <p class="hero-copy">Cada petición web tiene un ID de diagnóstico visible al pie. Usalo para encontrar sus llamadas a Vittles y buscar el mismo ID en VS Code.</p>
</section>

<div class="admin-shell">
    <div class="admin-overview">
        <div><span class="section-number">TRAZAS RECIENTES</span><strong>{{ $count }}</strong></div>
        <div><span class="section-number">ERRORES Y RECHAZOS</span><strong>{{ $errorCount }}</strong></div>
        <div><span class="section-number">ACCESO</span><strong>Solo local</strong></div>
    </div>

    <section class="panel admin-audit-guide" aria-labelledby="admin-audit-heading">
        <p class="section-number">INVESTIGACIÓN DEL AGENTE · PRUEBAS GUARDADAS</p>
        <h2 id="admin-audit-heading">Cómo se descubrió el contrato.</h2>
        <p class="muted">Este recorrido resume requests y respuestas de la auditoría independiente. Los botones del laboratorio generan peticiones nuevas y sus trazas aparecen más abajo; pueden devolver otro ID de orden o un 429 si el límite está ocupado.</p>
        <ol class="admin-audit-list">
            @foreach ($investigation as $moment)
                <li><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><strong>{{ $moment['title'] }}</strong><p>{{ $moment['result'] }}</p></div></li>
            @endforeach
        </ol>
        <a class="secondary-button admin-audit-link" href="{{ route('vittles.audit') }}#investigation">Ver requests, decisiones y evidencia por paso →</a>
        <p class="muted">Capturas originales redactadas: <code>docs/AUDIT_INDEPENDIENTE/</code>. Las trazas de esta página corresponden a la sesión actual, no sustituyen esas capturas.</p>
    </section>

    <section class="panel admin-probes">
        <p class="section-number">LABORATORIO DE API</p>
        <h2>Probá un endpoint.</h2>
        <p class="muted">Cada intento aparece abajo como una traza. Las respuestas conservan los campos técnicos del mock; los secretos se ocultan.</p>

        @if ($errors->any())
            <div class="notice error" role="alert">{{ $errors->first() }}</div>
        @endif
        @if (session('admin_probe_error'))
            <div class="notice error" role="alert">{{ session('admin_probe_error') }} Revisá la traza para ver la respuesta HTTP.</div>
        @endif
        @if (session('admin_probe_result'))
            <div class="probe-output" aria-live="polite">
                <span class="section-number">ÚLTIMA PRUEBA · {{ session('admin_probe_result.probe') }}</span>
                <pre>{{ json_encode(session('admin_probe_result.data'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}</pre>
            </div>
        @endif

        <div class="probe-grid">
            <form method="post" action="{{ route('admin.probe') }}" class="probe-card">
                @csrf<input type="hidden" name="probe" value="auth">
                <span class="probe-method">POST /oauth/token</span>
                <p>Autenticación. El token se oculta en el resultado y en los logs.</p>
                <button class="probe-button" type="submit">Probar autenticación →</button>
            </form>
            <form method="post" action="{{ route('admin.probe') }}" class="probe-card">
                @csrf<input type="hidden" name="probe" value="locations">
                <span class="probe-method">GET /v1/locations</span>
                <label for="probe-cursor">Cursor opcional</label>
                <input id="probe-cursor" name="cursor" placeholder="0, 2 o 4" inputmode="numeric">
                <button class="probe-button" type="submit">Consultar página →</button>
            </form>
            <form method="post" action="{{ route('admin.probe') }}" class="probe-card">
                @csrf<input type="hidden" name="probe" value="catalog">
                <span class="probe-method">GET locations + menús</span>
                <p>Recorre todas las páginas y consulta el menú de cada sede.</p>
                <button class="probe-button" type="submit">Probar recorrido completo →</button>
            </form>
            <form method="post" action="{{ route('admin.probe') }}" class="probe-card">
                @csrf<input type="hidden" name="probe" value="menu">
                <span class="probe-method">GET /v1/locations/{id}/menu</span>
                <label for="probe-location">Location ID</label>
                <input id="probe-location" name="location_id" value="loc_1004" required>
                <button class="probe-button" type="submit">Consultar menú →</button>
            </form>
            <form method="post" action="{{ route('admin.probe') }}" class="probe-card">
                @csrf<input type="hidden" name="probe" value="order-search">
                <span class="probe-method">GET /v1/orders?client_ref</span>
                <label for="probe-ref">Referencia</label>
                <input id="probe-ref" name="client_ref" value="{{ session('vittles_last_result.client_ref', '') }}" placeholder="heyt-..." required>
                <button class="probe-button" type="submit">Buscar orden →</button>
            </form>
            <form method="post" action="{{ route('admin.probe') }}" class="probe-card">
                @csrf<input type="hidden" name="probe" value="order-detail">
                <span class="probe-method">GET /v1/orders/{id}</span>
                <label for="probe-order-id">Order ID</label>
                <input id="probe-order-id" name="order_id" value="{{ session('vittles_last_result.order.id', '') }}" placeholder="ord_5501" required>
                <button class="probe-button" type="submit">Leer orden →</button>
            </form>
            <form method="post" action="{{ route('admin.probe') }}" class="probe-card">
                @csrf<input type="hidden" name="probe" value="order-rejected">
                <span class="probe-method">POST /v1/orders · rechazo</span>
                <p>Envía un payload vacío sin contexto de sede para observar el 200 + REJECTED. No crea una orden en este mock.</p>
                <button class="probe-button" type="submit">Probar rechazo →</button>
            </form>
            <form method="post" action="{{ route('admin.probe') }}" class="probe-card">
                @csrf<input type="hidden" name="probe" value="order-documented">
                <span class="probe-method">POST /v1/orders · guía oficial</span>
                <p>Reproduce el JSON del ejemplo sin el header de sede que la guía omitió. En el mock devuelve 200 + REJECTED; comprobá el resultado y la traza.</p>
                <button class="probe-button" type="submit">Reproducir ejemplo oficial →</button>
            </form>
            <form method="post" action="{{ route('admin.probe') }}" class="probe-card probe-card-create">
                @csrf<input type="hidden" name="probe" value="order-create">
                <span class="probe-method">POST /v1/orders · creación</span>
                <label for="create-location">Location ID</label>
                <input id="create-location" name="location_id" value="loc_1001" required>
                <label for="create-item">Producto exacto</label>
                <input id="create-item" name="item_name" value="Buffalo Wings (12)" required>
                <label for="create-key">Clave de solicitud</label>
                <input id="create-key" name="request_key" value="admin-demo" required>
                <p>Crea una orden real en el mock si esa referencia aún no existe. Repetir la prueba recupera la misma orden.</p>
                <button class="probe-button" type="submit">Crear o recuperar orden →</button>
            </form>
        </div>
    </section>

    <section class="panel admin-traces" id="traces">
        <div class="traces-heading"><div><p class="section-number">REGISTRO HTTP</p><h2>Peticiones y respuestas.</h2></div><nav class="trace-filters" aria-label="Filtrar trazas"><a @class(['selected' => $filter === 'all']) href="{{ route('admin.index', ['filter' => 'all', 'id' => $searchId]) }}#traces">Todas</a><a @class(['selected' => $filter === 'errors']) href="{{ route('admin.index', ['filter' => 'errors', 'id' => $searchId]) }}#traces">Errores</a><a @class(['selected' => $filter === 'post']) href="{{ route('admin.index', ['filter' => 'post', 'id' => $searchId]) }}#traces">POST</a></nav></div>
        <form method="get" action="{{ route('admin.index') }}#traces" class="trace-search"><label for="trace-id">ID de diagnóstico</label><input id="trace-id" name="id" value="{{ $searchId }}" placeholder="Ej. 8f3a..." maxlength="16" pattern="[a-f0-9]{16}"><input type="hidden" name="filter" value="{{ $filter }}"><button type="submit">Buscar trazas</button><a href="{{ route('admin.index') }}#traces">Limpiar</a></form>
        <p class="muted">Últimas 120 trazas; un reintento aparece como otra fila. Para seguir el recorrido completo, buscá el ID en <code>app/storage/logs/integration.log</code> desde VS Code. El archivo de trazas rota al superar 2 MB. Los campos sensibles conocidos se redactan antes de guardarse.</p>
        <div class="trace-list">
            @forelse ($traces as $trace)
                @php
                    $isError = ($trace['transport_error'] ?? null) !== null || ($trace['status'] ?? 0) >= 400 || ($trace['response_body']['status'] ?? null) === 'REJECTED';
                @endphp
                <details class="trace-entry">
                    <summary>
                        <span class="trace-time">{{ $trace['at'] }}</span>
                        <span class="trace-method">{{ $trace['method'] }}</span>
                        <span class="trace-path">{{ $trace['path'] }}{{ count($trace['query']) ? '?'.http_build_query($trace['query']) : '' }}</span>
                        <span @class(['trace-status', 'trace-error' => $isError])>{{ $trace['status'] ?? 'SIN RESPUESTA' }}{{ ($trace['response_body']['status'] ?? null) === 'REJECTED' ? ' · REJECTED' : '' }}</span>
                        <span class="trace-duration">{{ $trace['duration_ms'] }} ms · intento {{ $trace['attempt'] }}</span>
                    </summary>
                    <div class="trace-detail">
                        <div><h3>Solicitud</h3><pre>{{ json_encode(['correlation_id' => $trace['correlation_id'] ?? null, 'operation_id' => $trace['operation_id'] ?? null, 'source' => $trace['source'] ?? null, 'method' => $trace['method'], 'path' => $trace['path'], 'query' => $trace['query'], 'headers' => $trace['request_headers'], 'body' => $trace['request_body']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}</pre></div>
                        <div><h3>Respuesta</h3><pre>{{ json_encode(['status' => $trace['status'], 'headers' => $trace['response_headers'], 'body' => $trace['response_body'], 'transport_error' => $trace['transport_error']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}</pre></div>
                    </div>
                </details>
            @empty
                <p class="muted">Todavía no hay trazas para este filtro. Ejecutá una prueba arriba o usá la integración.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
