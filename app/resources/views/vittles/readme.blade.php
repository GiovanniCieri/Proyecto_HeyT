@extends('layouts.heytruffle')

@section('title', 'README')

@section('content')
<section class="hero">
    <p class="eyebrow">INTEGRACIÓN VITTLES POS · DOCUMENTACIÓN</p>
    <h1>Cómo funciona <em>esta demo.</em></h1>
    <p class="hero-copy">Esta página explica el proyecto. El entregable solicitado incluye además un archivo README.md breve en la raíz del repositorio.</p>
</section>

<div class="readme-grid">
    <section class="panel">
        <p class="section-number">01 · ENTREGA OBLIGATORIA</p>
        <h2>Una integración pequeña.</h2>
        <p>El comando se autentica con Vittles, obtiene todas las locations, intenta leer cada menú y crea una orden de <strong>2 unidades</strong> en la sede indicada. Imprime el estado, ID y total. Al ejecutarlo dos veces con los mismos parámetros, recupera la orden existente.</p>
        <pre class="readme-command"># Desde la raíz, Windows PowerShell
.\scripts\install.ps1
.\scripts\start.ps1

# En otra terminal
cd app
php artisan vittles:order loc_1001 "Buffalo Wings (12)"</pre>
        <p class="muted">En macOS/Linux: <code>bash scripts/install.sh</code> y <code>bash scripts/start.sh</code>. El instalador pide las credenciales del README original del mock cuando faltan. El arranque abre Vittles en <code>127.0.0.1:8422</code> y la web en <code>127.0.0.1:8000</code>.</p>
    </section>
    <section class="panel">
        <p class="section-number">02 · DEMO WEB</p>
        <h2>La ampliación visual.</h2>
        <p>La web permite seleccionar varios productos del mismo menú, con 1 a 20 unidades por línea, y consultar pedidos confirmados por sede. Es una ampliación: <strong>el comando del ejercicio siempre envía 2</strong> de un solo producto. La combinación de productos, cantidades y cuenta local forma la referencia idempotente.</p>
        <p>El historial de Pedidos guarda confirmaciones observadas por esta aplicación. No es una lista completa del POS. ADMIN permite inspeccionar requests y respuestas del mock local.</p>
        <pre class="readme-command">php artisan vittles:show ord_5501
php artisan vittles:orders loc_1001</pre>
        <p class="muted"><code>vittles:show</code> consulta el POS por ID; <code>vittles:orders</code> consulta solo el historial local. Resultado es un comprobante inmediato; después se revisa el pedido desde Pedidos.</p>
        <p class="muted">Desde la raíz, <code>.\scripts\console.cmd</code> (Windows) o <code>bash scripts/console.sh</code> (macOS/Linux) abre el menú con las mismas cuentas, sedes, pedidos y diagnóstico ADMIN local. Desde allí podés ejecutar el comando del ejercicio sin escribirlo.</p>
    </section>
    <section class="panel">
        <p class="section-number">03 · DECISIONES</p>
        <h2>Qué protege la orden.</h2>
        <ul class="readme-list">
            <li>Coincidencia exacta del nombre y disponibilidad en el menú de la sede elegida.</li>
            <li>Total final aceptado del POS; la estimación visual no decide el cobro.</li>
            <li>Búsqueda por <code>client_ref</code> antes del POST y conciliación de resultados inciertos sin repetir el POST a ciegas.</li>
            <li>Estados separados: creada, existente, recuperada, rechazada o desconocida.</li>
        </ul>
    </section>
    <section class="panel">
        <p class="section-number">04 · FUERA DE ALCANCE A PROPÓSITO</p>
        <h2>Lo que decidimos no construir.</h2>
        <ul class="readme-list">
            <li>Pagos, envío, stock propio y gestión comercial: Vittles no los pide para esta integración.</li>
            <li>Recuperación de contraseña y verificación de email: las cuentas son solo para la demo local.</li>
            <li>Despliegue y conexión a un POS real: el contrato verificado corresponde al mock entregado.</li>
            <li>Garantía <em>exactly once</em> entre máquinas: el mock no impide duplicados atómicamente por <code>client_ref</code>.</li>
        </ul>
        <p class="muted">“Dejé fuera a propósito” significa enumerar decisiones conscientes de alcance y explicar por qué no son necesarias para cumplir el ejercicio.</p>
    </section>
    <section class="panel readme-wide">
        <p class="section-number">05 · TRANSPARENCIA</p>
        <h2>IA y límites conocidos.</h2>
        <p>Se usó Codex para contrastar la documentación con respuestas HTTP, proponer el diseño, implementar y revisar. El código y sus decisiones deben poder explicarse en la entrevista. El comportamiento observado del mock prevalece sobre su documentación cuando difieren; las correcciones están en <code>docs/Docs_API/vittles/FIX_API_DOCS.md</code>.</p>
        <a class="text-link" href="{{ route('vittles.audit') }}">Recorrer cómo se descubrieron las discrepancias →</a>
    </section>
</div>
@endsection
