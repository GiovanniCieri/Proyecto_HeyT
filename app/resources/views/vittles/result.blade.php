@extends('layouts.heytruffle')

@section('title', 'Resultado')

@section('content')
<section class="hero">
    <p class="eyebrow">INTEGRACIÓN VITTLES POS · 03 RESULTADO</p>
    <h1>Una respuesta que deja todo <em>claro.</em></h1>
    <p class="hero-copy">Cada estado explica si se creó una orden, se recuperó una anterior o falta conciliar un resultado incierto.</p>
</section>

<div class="content-grid">
    <section class="panel">
        <p class="section-number">03 · RESULTADO</p>
        <h2>{{ match ($result['status']) { 'CREATED' => 'Pedido confirmado.', 'EXISTING' => 'Pedido ya existente.', 'RECOVERED' => 'Pedido recuperado.', 'REJECTED' => 'Pedido rechazado.', default => 'Resultado incierto.' } }}</h2>
        <div class="result-box">
            <p class="result-status">{{ $result['status'] }}</p>
            <p class="result-id">{{ $result['order']['id'] ?? 'Sin ID confirmado' }}</p>
            <div class="detail-row"><span>Location</span><strong>{{ $result['location']['name'] }} · {{ $result['location']['id'] }}</strong></div>
            <div class="detail-row"><span>Producto</span><strong>{{ $result['item']['name'] }} × {{ $result['quantity'] }}</strong></div>
            <div class="detail-row"><span>Total devuelto por Vittles</span><strong class="estimate">{{ isset($result['order']) ? '$'.$result['order']['total'] : '—' }}</strong></div>
        </div>
        @if (isset($result['message']))
            <div class="notice {{ $result['status'] === 'UNKNOWN' ? 'warning' : 'error' }}" role="alert">{{ $result['message'] }}</div>
        @endif
        <a class="secondary-button" href="{{ route('vittles.order') }}">Volver a nueva orden →</a>
        @if (isset($result['order']))
            <a class="secondary-button" href="{{ route('vittles.orders', ['location' => $result['location']['id']]) }}">Ver pedidos de esta sede →</a>
        @endif
    </section>

    <aside class="panel coverage">
        <p class="section-number">RASTRO DE LA OPERACIÓN</p>
        <h2>Qué ocurrió.</h2>
        <div class="coverage-line"><span class="orange-dot"></span>{{ count($result['catalog']['locations']) }} locations encontradas</div>
        <div class="coverage-line"><span class="orange-dot"></span>{{ count($result['catalog']['menus']) }} menús consultados</div>
        <div class="coverage-line"><span class="orange-dot"></span>Ítem validado en la sede elegida</div>
        <div class="coverage-line"><span class="orange-dot"></span>Referencia consultada antes del POST</div>
        <p class="muted">Referencia: <code>{{ $result['client_ref'] }}</code></p>
        <p class="muted">La segunda ejecución de la misma solicitud debe conservar el mismo ID y total. Si el resultado es incierto, la aplicación evita un nuevo POST automático.</p>
    </aside>
</div>
@endsection
