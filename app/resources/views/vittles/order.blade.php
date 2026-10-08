@extends('layouts.heytruffle')

@section('title', 'Nueva orden')

@section('content')
<section class="hero">
    <p class="eyebrow">INTEGRACIÓN VITTLES POS · 01 ÓRDENES</p>
    <h1>Una orden, directo al <em>POS.</em></h1>
    <p class="hero-copy">Elegí una sede y uno o varios productos. Consultamos todos los menús y enviamos una sola orden a la sede elegida.</p>
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
            <label>Productos y cantidades</label>
            <div id="order-items" class="order-items"></div>
            <p id="menu-state" class="field-hint" aria-live="polite"></p>
            <p class="field-help">Poné 0 en los productos que no quieras. Cada producto admite de 1 a 20 unidades; el comando del ejercicio conserva un solo producto con cantidad 2.</p>
            <label for="request_key">Identificador de compra <span class="muted">(opcional)</span></label>
            <input id="request_key" name="request_key" type="text" maxlength="80" value="{{ old('request_key', 'demo') }}">
            <p class="field-help">Para tu cuenta, la misma sede, combinación de productos, cantidades e identificador recuperan el pedido existente. Cambialo para iniciar otra compra.</p>
            <div class="detail-row"><span>Total estimado</span><strong id="estimate" class="estimate">—</strong></div>
            <button id="submit-order" class="primary-button" type="submit" @disabled(count($catalog['locations']) === 0)>Crear orden →</button>
            <p class="fine-print">El total definitivo es el que devuelve Vittles. Los pedidos confirmados quedan en el historial local.</p>
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
    const itemsContainer = document.getElementById('order-items');
    const state = document.getElementById('menu-state');
    const estimate = document.getElementById('estimate');
    const submit = document.getElementById('submit-order');
    const previousItems = @json(old('items', []));

    function updateEstimate() {
        let total = 0;
        let selected = 0;
        for (const input of itemsContainer.querySelectorAll('input[data-price]')) {
            const quantity = Number(input.value);
            if (!Number.isInteger(quantity) || quantity < 0 || quantity > 20) {
                estimate.textContent = '—';
                submit.disabled = true;
                return;
            }
            if (quantity > 0) selected++;
            total += Number(input.dataset.price) * quantity;
        }
        estimate.textContent = selected ? '$' + total.toFixed(2) : '—';
        submit.disabled = selected === 0 || selected > 20;
    }

    function updateItems() {
        const menu = menus[locationField.value];
        itemsContainer.replaceChildren();
        if (menu?.status !== 'loaded') {
            state.textContent = menu?.status === 'forbidden' ? 'Esta sede no permite leer el menú (403).' : 'No se pudo consultar este menú.';
            submit.disabled = true;
            estimate.textContent = '—';
            return;
        }
        const available = menu.items.filter(item => item.available);
        for (const [index, item] of available.entries()) {
            const row = document.createElement('div');
            row.className = 'order-item-row';
            const label = document.createElement('label');
            label.htmlFor = `item-quantity-${index}`;
            label.textContent = `${item.name} · $${item.price}`;
            const id = document.createElement('input');
            id.type = 'hidden';
            id.name = `items[${index}][item_id]`;
            id.value = item.id;
            const quantity = document.createElement('input');
            quantity.type = 'number';
            quantity.id = label.htmlFor;
            quantity.name = `items[${index}][quantity]`;
            quantity.min = '0';
            quantity.max = '20';
            quantity.step = '1';
            quantity.value = previousItems.find(line => line.item_id === item.id)?.quantity ?? '0';
            quantity.dataset.price = item.price;
            quantity.addEventListener('input', updateEstimate);
            row.append(label, id, quantity);
            itemsContainer.append(row);
        }
        state.textContent = available.length ? 'Solo se muestran productos disponibles en esta sede.' : 'Esta sede no tiene productos disponibles.';
        updateEstimate();
    }

    locationField.addEventListener('change', updateItems);
    updateItems();
})();
</script>
@endpush
