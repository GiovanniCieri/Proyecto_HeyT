<?php

namespace Tests\Feature;

use App\Services\Vittles\TraceStore;
use App\Services\Vittles\VittlesClient;
use App\Services\Vittles\VittlesException;
use App\Support\DiagnosticLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DiagnosticLogTest extends TestCase
{
    /**
     * Verifica que la respuesta web muestre un ID buscable en integration-testing.log.
     * Permite ubicar el método que atendió un fallo sin inspeccionar el navegador del POS.
     */
    public function test_web_response_id_is_searchable_in_the_application_log(): void
    {
        $response = $this->get('/login');
        $id = $response->headers->get('X-Diagnostic-Id');

        $this->assertMatchesRegularExpression('/^[a-f0-9]{16}$/', $id);
        $log = file_get_contents(storage_path('logs/integration-testing.log'));
        $this->assertStringContainsString($id, $log);
        $this->assertStringContainsString('web.request.finished', $log);
        $this->assertStringContainsString('TraceWebRequest::handle', $log);
    }

    /**
     * Simula una request web que consulta Vittles y exige el mismo ID en respuesta,
     * traza HTTP y log; también comprueba que el bearer nunca llegue al log.
     */
    public function test_one_id_links_the_web_response_provider_trace_and_log(): void
    {
        config()->set('vittles.base_url', 'http://vittles.test');
        config()->set('vittles.client_id', 'test-id');
        config()->set('vittles.client_secret', 'test-secret');
        Http::fake([
            'vittles.test/oauth/token' => Http::response(['access_token' => 'private-token', 'expires' => 90], 200),
            'vittles.test/v1/locations' => Http::response(['data' => []], 200),
        ]);
        Route::middleware('web')->get('/_test/diagnostic', function (VittlesClient $client) {
            $client->get('/v1/locations');

            return response('ok');
        })->name('test.diagnostic');

        $response = $this->get('/_test/diagnostic');
        $response->assertOk();
        $id = $response->headers->get('X-Diagnostic-Id');
        $trace = app(TraceStore::class)->recent(1)[0];
        $this->assertSame($id, $trace['correlation_id']);
        $this->assertSame('App\\Services\\Vittles\\VittlesClient::send', $trace['source']);

        $log = file_get_contents(storage_path('logs/integration-testing.log'));
        $this->assertStringContainsString($id, $log);
        $this->assertStringContainsString('vittles.http.attempt', $log);
        $this->assertStringNotContainsString('private-token', $log);
    }

    /**
     * Envía campos permitidos y secretos al logger; solo el método debe persistir.
     * Detectaría una ampliación accidental del contexto que filtrase credenciales.
     */
    public function test_diagnostic_log_only_persists_allowlisted_fields(): void
    {
        app(DiagnosticLog::class)->event('warning', 'diagnostics.redaction.test', __METHOD__, [
            'client_secret' => 'private-secret-forbidden',
            'password' => 'private-password-forbidden',
            'method' => 'GET',
        ]);

        $lastLine = array_slice(file(storage_path('logs/integration-testing.log')), -1)[0];
        $this->assertStringContainsString('diagnostics.redaction.test', $lastLine);
        $this->assertStringContainsString('"method":"GET"', $lastLine);
        $this->assertStringNotContainsString('private-secret-forbidden', $lastLine);
        $this->assertStringNotContainsString('private-password-forbidden', $lastLine);
    }

    /**
     * Simula HTTP 429 en /oauth/token y exige un mensaje específico de rate limit.
     * El límite también afecta la autenticación, no solo los GET del POS.
     */
    public function test_rate_limit_during_authentication_is_reported_accurately(): void
    {
        config()->set('vittles.base_url', 'http://vittles.test');
        config()->set('vittles.client_id', 'test-id');
        config()->set('vittles.client_secret', 'test-secret');
        Http::fake(['vittles.test/oauth/token' => Http::response(['error' => 'slow down'], 429)]);

        $this->expectException(VittlesException::class);
        $this->expectExceptionMessage('límite de peticiones');

        app(VittlesClient::class)->inspectAuthentication();
    }
}
