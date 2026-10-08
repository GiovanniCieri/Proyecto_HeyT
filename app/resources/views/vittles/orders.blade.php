@extends('layouts.heytruffle')

@section('title', 'Pedidos')

@section('content')
<section class="hero">
    <p class="eyebrow">INTEGRACIÓN VITTLES POS · PEDIDOS</p>
    <h1>Pedidos por <em>sede.</em></h1>
    <p class="hero-copy">Historial local de órdenes confirmadas por esta integración. Vittles no ofrece un endpoint para listar todos los pedidos de una location.</p>
</section>

<div class="orders-shell">
    <section class="panel">
        <div class="orders-toolbar">
            <div><p class="section-number">HISTORIAL LOCAL</p><h2>{{ count($orders) }} {{ count($orders) === 1 ? 'pedido registrado' : 'pedidos registrados' }}.</h2></div>
            <form method="get" action="{{ route('vittles.orders') }}" class="orders-filter">
                <label for="location-filter">Filtrar por location</label>
                <select id="location-filter" name="location" onchange="this.form.submit()">
                    <option value="">Todas las sedes</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->location_id }}" @selected($locationId === $location->location_id)>{{ $location->location_name }} · {{ $location->location_id }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <p class="muted">Se muestran los últimos 100 registros confirmados desde que se agregó este historial, compartidos entre las cuentas locales de la demo. Repetir una intención ya confirmada la incorpora sin duplicarla. El mock pierde sus órdenes al reiniciarse; el historial local puede conservar pedidos de una ejecución anterior.</p>
    </section>

    @forelse (collect($orders)->groupBy('location_id') as $locationOrders)
        <section class="panel orders-location">
            <p class="section-number">{{ $locationOrders->first()->location_id }}</p>
            <h2>{{ $locationOrders->first()->location_name }}</h2>
            <div class="orders-list">
                @foreach ($locationOrders as $order)
                    <article class="order-card">
                        <div><p class="order-card-label">{{ $order->order_id }}</p><h3>{{ $order->item_name }} <span>× {{ $order->quantity }}</span></h3><p class="muted">Confirmado localmente: {{ $order->updated_at }} · Ref. <code>{{ $order->client_ref }}</code></p></div>
                        <strong>${{ number_format((float) $order->total, 2, '.', '') }}</strong>
                    </article>
                @endforeach
            </div>
        </section>
    @empty
        <section class="panel orders-empty">
            <p class="section-number">SIN PEDIDOS</p>
            <h2>Todavía no hay pedidos confirmados.</h2>
            <p class="muted">Creá uno desde la web o ejecutá el comando Artisan. Los rechazos y resultados inciertos no aparecen como pedidos realizados.</p>
            <a class="secondary-button" href="{{ route('vittles.order') }}">Crear una orden →</a>
        </section>
    @endforelse
</div>
@endsection
