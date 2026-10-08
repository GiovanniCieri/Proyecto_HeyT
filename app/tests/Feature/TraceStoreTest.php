<?php

namespace Tests\Feature;

use App\Services\Vittles\TraceStore;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\Response;
use Tests\TestCase;

class TraceStoreTest extends TestCase
{
    public function test_sensitive_values_are_redacted_before_a_trace_is_saved(): void
    {
        $traces = app(TraceStore::class);
        $traces->record(
            'POST', '/oauth/token', [],
            ['client_id' => 'partner-demo', 'client_secret' => 'private-secret', 'customer' => ['phone' => 'private-phone']],
            ['Authorization' => 'Bearer private-token'],
            new Response(new PsrResponse(200, ['Content-Type' => 'application/json'], json_encode(['access_token' => 'private-token', 'expires' => 90, 'trace_id' => 'trace-123']))),
            12, 1,
        );

        $trace = $traces->recent(1)[0];
        $encoded = json_encode($trace);
        $this->assertStringNotContainsString('private-secret', $encoded);
        $this->assertStringNotContainsString('private-token', $encoded);
        $this->assertStringNotContainsString('private-phone', $encoded);
        $this->assertSame('[REDACTED]', $trace['response_body']['access_token']);
        $this->assertSame('trace-123', $trace['response_body']['trace_id']);
    }

    public function test_admin_is_unavailable_outside_local_environment(): void
    {
        $this->get('/admin')->assertNotFound();
    }
}
