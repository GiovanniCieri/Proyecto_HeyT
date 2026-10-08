@extends('layouts.heytruffle')

@section('title', 'Sedes y menús')

@section('content')
<section class="hero">
    <p class="eyebrow">INTEGRACIÓN VITTLES POS · 02 MENÚS</p>
    <h1>Cada sede tiene su <em>menú.</em></h1>
    <p class="hero-copy">Precios y disponibilidad se comprueban en cada location. Una sede sin acceso se muestra como tal.</p>
</section>

<div class="content-grid">
    <section class="panel">
        <p class="section-number">02 · LOCATIONS</p>
        <h2>Estado de lectura.</h2>
        @if ($catalogError)
            <div class="notice error" role="alert">{{ $catalogError }}</div>
        @endif
        <div class="location-list">
            @forelse ($catalog['locations'] as $location)
                @php $menu = $catalog['menus'][$location['id']] ?? ['status' => 'error', 'items' => []]; @endphp
                <button class="location-choice" type="button" data-location="{{ $location['id'] }}" aria-pressed="false">
                    <span><strong>{{ $location['name'] }}</strong><small>{{ $location['id'] }}{{ $location['active'] ? '' : ' · inactiva' }}</small></span>
                    <span class="location-status">{{ match ($menu['status']) { 'loaded' => 'Menú cargado', 'forbidden' => 'Sin acceso · 403', default => 'Error de lectura' } }}</span>
                </button>
            @empty
                <p class="muted">No hay locations disponibles.</p>
            @endforelse
        </div>
    </section>

    <aside class="panel">
        <p class="section-number">DETALLE DE SEDE</p>
        <h2 id="location-title">Elegí una sede.</h2>
        <p id="location-caption" class="muted">Los productos y precios se muestran para la location seleccionada.</p>
        <div id="menu-items" aria-live="polite"></div>
    </aside>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const catalog = @json($catalog);
    const buttons = [...document.querySelectorAll('.location-choice')];
    const title = document.getElementById('location-title');
    const caption = document.getElementById('location-caption');
    const container = document.getElementById('menu-items');
    function show(id) {
        buttons.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.location === id)));
        const location = catalog.locations.find(entry => entry.id === id);
        const menu = catalog.menus[id];
        title.textContent = location?.name ?? 'Sede desconocida';
        container.replaceChildren();
        if (menu?.status !== 'loaded') {
            caption.textContent = menu?.status === 'forbidden' ? 'La sede inactiva devuelve 403 al consultar el menú.' : (menu?.message ?? 'No se pudo leer el menú.');
            return;
        }
        caption.textContent = `${menu.items.length} productos del menú de esta location.`;
        for (const item of menu.items) {
            const row = document.createElement('div');
            row.className = 'menu-row';
            const name = document.createElement('span');
            name.textContent = item.name;
            const meta = document.createElement('strong');
            meta.textContent = item.available ? `$${item.price}` : 'Agotado';
            row.append(name, meta);
            container.append(row);
        }
    }
    buttons.forEach(button => button.addEventListener('click', () => show(button.dataset.location)));
    if (buttons.length) show(buttons[0].dataset.location);
})();
</script>
@endpush
