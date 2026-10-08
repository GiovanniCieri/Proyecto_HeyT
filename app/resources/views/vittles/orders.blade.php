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
                        @php $lines = json_decode($order->items_json ?? '', true) ?: [['name' => $order->item_name, 'quantity' => $order->quantity]]; @endphp
                        <div><p class="order-card-label">{{ $order->order_id }}</p><h3>{{ count($lines) }} {{ count($lines) === 1 ? 'producto' : 'productos' }} <span>· {{ $order->quantity }} unidades</span></h3><p class="muted">{{ implode(' · ', array_map(fn ($line) => $line['name'].' × '.$line['quantity'], $lines)) }}</p><p class="muted">Confirmado localmente: {{ $order->updated_at }}</p></div>
                        <div><strong>${{ number_format((float) $order->total, 2, '.', '') }}</strong><a class="text-link" href="{{ route('vittles.order-detail', ['clientRef' => $order->client_ref]) }}">Ver detalle →</a></div>
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
