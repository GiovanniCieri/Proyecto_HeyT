@extends('layouts.heytruffle')

@section('title', 'Detalle del pedido')

@section('content')
<section class="hero">
    <p class="eyebrow">INTEGRACIÓN VITTLES POS · PEDIDO REGISTRADO</p>
    <h1>El detalle de tu <em>pedido.</em></h1>
    <p class="hero-copy">Confirmación guardada localmente por esta integración. El estado actual del POS puede consultarse con el comando <code>vittles:show</code>.</p>
</section>

<div class="orders-shell">
    <section class="panel">
        <p class="section-number">{{ $order->location_name }} · {{ $order->location_id }}</p>
        <h2>{{ $order->order_id }}</h2>
        @php $lines = json_decode($order->items_json ?? '', true) ?: [['name' => $order->item_name, 'quantity' => $order->quantity]]; @endphp
        @foreach ($lines as $line)
            <div class="detail-row"><span>{{ $line['name'] }}</span><strong>× {{ $line['quantity'] }}</strong></div>
        @endforeach
        <div class="detail-row"><span>Total confirmado por Vittles</span><strong class="estimate">${{ number_format((float) $order->total, 2, '.', '') }}</strong></div>
        <p class="muted">Referencia: <code>{{ $order->client_ref }}</code> · Última confirmación local: {{ $order->updated_at }}</p>
        <a class="secondary-button" href="{{ route('vittles.orders', ['location' => $order->location_id]) }}">Volver a pedidos →</a>
    </section>
</div>
@endsection
