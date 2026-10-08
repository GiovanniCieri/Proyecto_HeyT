@extends('layouts.heytruffle')

@section('title', 'Nueva orden')

@section('content')
<section class="hero">
    <p class="eyebrow">INTEGRACIÓN VITTLES POS · 01 ÓRDENES</p>
    <h1>Una orden, directo al <em>POS.</em></h1>
    <p class="hero-copy">Elegí una sede y un producto. Consultamos todos los menús y enviamos una orden a la sede elegida.</p>
</section>

<div class="content-grid">
    <section class="panel">
        <p class="section-number">01 · NUEVA ORDEN</p>
        <h2>Prepará el pedido.</h2>
        @if ($catalogError)
            <div class="notice error" role="alert">{{ $catalogError }}</div>
        @endif
        @if (session('operation_error'))
            <div class="notice error" role="alert">{{ session('operation_error') }}</div>
        @endif
        @if ($errors->any())
            <div class="notice error" role="alert">{{ $errors->first() }}</div>
        @endif
        <form method="post" action="{{ route('vittles.place') }}">
            @csrf
            <label for="location">Location de destino</label>
            <select id="location" name="location" required @disabled(count($catalog['locations']) === 0)>
                @foreach ($catalog['locations'] as $location)
                    <option value="{{ $location['id'] }}" @selected(old('location') === $location['id'])>
                        {{ $location['name'] }} · {{ $location['id'] }}{{ $location['active'] ? '' : ' · inactiva' }}
                    </option>
                @endforeach
            </select>
            <label for="item">Producto por nombre exacto</label>
            <select id="item" name="item" required @disabled(count($catalog['locations']) === 0)></select>
            <p id="menu-state" class="field-hint" aria-live="polite"></p>
            <div class="detail-row"><span>Cantidad</span><strong>2</strong></div>
            <div class="detail-row"><span>Total estimado</span><strong id="estimate" class="estimate">—</strong></div>
            <button id="submit-order" class="primary-button" type="submit" @disabled(count($catalog['locations']) === 0)>Crear orden →</button>
            <p class="fine-print">El total definitivo es el que devuelve Vittles. Repetir la misma solicitud recupera la orden existente.</p>
        </form>
    </section>

    <aside class="panel coverage">
        <p class="section-number">COBERTURA DEL POS</p>
        <h2>Cada sede, consultada.</h2>
        <p>La integración recorre todas las páginas de locations e intenta leer el menú de cada una antes de crear la orden.</p>
        @php
            $loaded = count(array_filter($catalog['menus'], fn ($menu) => $menu['status'] === 'loaded'));
            $forbidden = count(array_filter($catalog['menus'], fn ($menu) => $menu['status'] === 'forbidden'));
        @endphp
        <div class="coverage-line"><span class="orange-dot"></span>{{ count($catalog['locations']) }} locations encontradas</div>
        <div class="coverage-line"><span class="orange-dot"></span>{{ count($catalog['menus']) }} menús consultados</div>
        <div class="coverage-line"><span class="orange-dot"></span>{{ $loaded }} menús cargados</div>
        <div class="coverage-line"><span class="orange-dot"></span>{{ $forbidden }} sedes sin acceso al menú</div>
        <a class="text-link" href="{{ route('vittles.locations') }}">Explorar sedes y menús →</a>
    </aside>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const menus = @json($catalog['menus']);
    const locationField = document.getElementById('location');
    const itemField = document.getElementById('item');
    const state = document.getElementById('menu-state');
    const estimate = document.getElementById('estimate');
    const submit = document.getElementById('submit-order');
    const previousItem = @json(old('item', ''));

    function updateEstimate() {
        const menu = menus[locationField.value];
        const item = menu?.items?.find(entry => entry.name === itemField.value);
        estimate.textContent = item ? '$' + (Number(item.price) * 2).toFixed(2) : '—';
    }

    function updateItems() {
        const menu = menus[locationField.value];
        itemField.replaceChildren();
        if (menu?.status !== 'loaded') {
            state.textContent = menu?.status === 'forbidden' ? 'Esta sede no permite leer el menú (403).' : 'No se pudo consultar este menú.';
            itemField.disabled = true;
            submit.disabled = true;
            estimate.textContent = '—';
            return;
        }
        const available = menu.items.filter(item => item.available);
        for (const item of available) {
            const option = document.createElement('option');
            option.value = item.name;
            option.textContent = `${item.name} · $${item.price}`;
            itemField.append(option);
        }
        if (available.some(item => item.name === previousItem)) itemField.value = previousItem;
        state.textContent = available.length ? 'Solo se muestran productos disponibles en esta sede.' : 'Esta sede no tiene productos disponibles.';
        itemField.disabled = available.length === 0;
        submit.disabled = available.length === 0;
        updateEstimate();
    }

    locationField.addEventListener('change', updateItems);
    itemField.addEventListener('change', updateEstimate);
    updateItems();
})();
</script>
@endpush
