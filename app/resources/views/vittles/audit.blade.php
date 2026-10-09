@extends('layouts.heytruffle')

@section('title', 'Auditoría de la integración')

@section('content')
@php
    $keys = array_keys($steps);
    $index = array_search($selected, $keys, true);
    $step = $steps[$selected];
    $previous = $index > 0 ? $keys[$index - 1] : null;
    $next = $index < count($keys) - 1 ? $keys[$index + 1] : null;
@endphp
<section class="hero audit-hero">
    <p class="eyebrow">INTEGRACIÓN VITTLES POS · EVIDENCIA</p>
    <h1>Del documento al <em>comportamiento real.</em></h1>
    <p class="hero-copy">Qué esperábamos, qué respondió el mock y cómo cambió la integración. Este recorrido muestra evidencia ya verificada; navegarlo no envía pedidos ni requests a Vittles.</p>
</section>

<div class="audit-shell">
    <section class="panel audit-investigation" id="investigation" aria-labelledby="investigation-heading">
        <p class="section-number">INVESTIGACIÓN INDEPENDIENTE · {{ count($investigation) }} MOMENTOS</p>
        <h2 id="investigation-heading">Cómo llegó el agente al contrato real.</h2>
        <p class="muted">Partió del enunciado y de API_DOCS.md. Diseñó requests sobre el servidor en marcha, conservó las respuestas redactadas y recién entonces decidió cómo adaptar la integración. Este registro muestra pruebas ya realizadas; abrir la página no vuelve a crear órdenes.</p>
        <ol class="investigation-timeline">
            @foreach ($investigation as $moment)
                <li>
                    <span class="investigation-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <div>
                        <h3>{{ $moment['title'] }}</h3>
                        <p><strong>Qué probó:</strong> {{ $moment['action'] }}</p>
                        <p><strong>Qué observó:</strong> {{ $moment['result'] }}</p>
                        <small>Evidencia: <code>{{ $moment['evidence'] }}</code></small>
                    </div>
                </li>
            @endforeach
        </ol>
        <p class="audit-boundary">La prueba secuencial mostró cómo recuperar una orden sin otro POST. No prueba atomicidad entre procesos ni resuelve por sí sola un POST cuya respuesta se perdió.</p>
    </section>

    <section class="panel audit-panel" aria-labelledby="audit-heading">
        <p class="section-number">CONTRATO · {{ count($steps) }} HALLAZGOS</p>
        <h2 id="audit-heading" class="audit-heading">{{ $step['title'] }}</h2>
        <nav class="audit-steps" aria-label="Hallazgos del contrato">
            @foreach ($steps as $key => $candidate)
                <a href="{{ route('vittles.audit', ['step' => $key]) }}"
                   @class(['audit-step', 'selected' => $selected === $key])
                   @if ($selected === $key) aria-current="step" @endif>
                    <span>{{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}</span> {{ $candidate['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="audit-columns">
            <article class="audit-card">
                <p class="section-number">DOCUMENTACIÓN OFICIAL</p>
                <h3>Lo que esperábamos.</h3>
                <p>{{ $step['official'] }}</p>
                <pre><code>{{ $step['official_code'] }}</code></pre>
            </article>
            <article class="audit-card">
                <p class="section-number">RESPUESTA OBSERVADA</p>
                <h3>El punto de ruptura.</h3>
                <p>{{ $step['observed'] }}</p>
                <pre><code>{{ $step['observed_code'] }}</code></pre>
            </article>
            <article class="audit-card">
                <p class="section-number">DECISIÓN IMPLEMENTADA</p>
                <h3>Cómo continuamos.</h3>
                <p>{{ $step['decision'] }}</p>
                <pre><code>{{ $step['implementation'] }}</code></pre>
            </article>
        </div>

        <div class="audit-impact">
            <strong>Si siguiéramos la documentación literalmente</strong>
            <p>{{ $step['impact'] }}</p>
        </div>

        <details class="audit-method" open>
            <summary>Cómo lo encontró el agente auditor</summary>
            <p class="muted">Cada hallazgo sigue la misma secuencia: afirmación de la guía, request HTTP, respuesta guardada y decisión aplicada. Las capturas del agente están redactadas y se conservan en el repositorio.</p>
            <ol>
                <li><strong>Hipótesis de la guía</strong><span>{{ $step['claim'] }}</span></li>
                <li><strong>Prueba dirigida al mock</strong><span>{{ $step['probe'] }}</span></li>
                <li><strong>Discrepancia registrada</strong><span>{{ $step['difference'] }}</span></li>
                <li><strong>Confirmación y protección</strong><span>{{ $step['confirmation'] }}</span></li>
            </ol>
        </details>

        <div class="audit-provenance">
            <p class="section-number">EVIDENCIA Y FUENTES</p>
            <p>{{ $step['evidence'] }}</p>
            <p class="muted">Referencia: <code>{{ $step['source'] }}</code>. Contrato corregido: <code>docs/Docs_API/vittles/FIX_API_DOCS.md</code>. Informe del agente: <code>docs/AUDIT_INDEPENDIENTE/INFORME.md</code>.</p>
        </div>

        <div class="audit-navigation">
            <span>Hallazgo {{ $index + 1 }} de {{ count($steps) }}</span>
            <div>
                @if ($previous)
                    <a class="secondary-button" href="{{ route('vittles.audit', ['step' => $previous]) }}">← Anterior</a>
                @endif
                @if ($next)
                    <a class="primary-button" href="{{ route('vittles.audit', ['step' => $next]) }}">Siguiente hallazgo →</a>
                @else
                    <a class="primary-button" href="{{ route('vittles.order') }}">Ver la integración funcionando →</a>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
