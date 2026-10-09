<?php

namespace App\Services\Vittles;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class VittlesClient
{
    private ?string $token = null;

    private int $tokenExpiresAt = 0;

    private array $lastAuthentication = [];

    private string $operationId;

    /** Asigna un ID a la operación para unir sus intentos HTTP en las trazas. */
    public function __construct(private readonly TraceStore $traces)
    {
        $this->operationId = bin2hex(random_bytes(5));
    }

    /** Punto de entrada para lecturas autenticadas; send aplica reintentos seguros de GET. */
    public function get(string $path, array $query = []): array
    {
        return $this->send('GET', $path, $query);
    }

    /** Añade el header de sede exigido por el mock, omitido en API_DOCS.md. */
    public function createOrder(array $payload, string $locationId): array
    {
        return $this->send('POST', '/v1/orders', $payload, ['X-Vittles-Location' => $locationId]);
    }

    /** Fuerza una autenticación de diagnóstico y devuelve metadatos con el token oculto. */
    public function inspectAuthentication(): array
    {
        $this->token = null;
        $this->authenticate();

        return [
            'access_token' => '[REDACTED]',
            'token_type' => $this->lastAuthentication['token_type'] ?? null,
            'expires' => $this->lastAuthentication['expires'] ?? null,
            'response_keys' => array_keys($this->lastAuthentication),
        ];
    }

    /** Provoca un rechazo controlado para verificar el 200/REJECTED sin crear una orden. */
    public function probeRejectedOrder(): array
    {
        return $this->send('POST', '/v1/orders', [
            'location_id' => 'loc_1001',
            'client_ref' => 'heyt-admin-invalid-probe',
            'items' => [],
        ]);
    }

    /** Reproduce el JSON publicado sin el header de sede para mostrar el rechazo que descubrió la auditoría. */
    public function probeDocumentedOrder(): array
    {
        return $this->send('POST', '/v1/orders', [
            'location_id' => 'loc_1001',
            'client_ref' => 'heyt-admin-docs-'.bin2hex(random_bytes(5)),
            'customer' => ['name' => 'Jane D.', 'phone' => '+13055550101'],
            'items' => [['item_id' => 'itm_88', 'quantity' => 2]],
        ]);
    }

    /**
     * Obtiene un bearer con las credenciales de entorno. El mock responde expires,
     * no expires_in; el token se renueva antes de vencer y nunca se registra en claro.
     */
    private function authenticate(): void
    {
        $id = config('vittles.client_id');
        $secret = config('vittles.client_secret');

        if (! is_string($id) || $id === '' || ! is_string($secret) || $secret === '') {
            throw new VittlesException('Faltan VITTLES_CLIENT_ID o VITTLES_CLIENT_SECRET en .env.');
        }

        $payload = ['client_id' => $id, 'client_secret' => $secret];
        $started = microtime(true);
        try {
            $response = Http::acceptJson()->timeout(6)->connectTimeout(2)->post($this->url('/oauth/token'), $payload);
        } catch (ConnectionException) {
            $this->traces->record('POST', '/oauth/token', [], $payload, [], null, $this->elapsed($started), 1, 'Conexión interrumpida', $this->operationId, __METHOD__);
            throw new VittlesException('No se pudo conectar con Vittles para autenticarse.');
        }
        $this->traces->record('POST', '/oauth/token', [], $payload, [], $response, $this->elapsed($started), 1, operationId: $this->operationId, source: __METHOD__);

        if ($response->status() === 429) {
            throw new VittlesException('Vittles alcanzó el límite de peticiones (HTTP 429). Esperá a que se libere la ventana de 60 segundos.', 429);
        }

        if (! $response->successful()) {
            throw new VittlesException('Vittles rechazó la autenticación (HTTP '.$response->status().').', $response->status());
        }

        $data = $this->jsonObject($response);
        if (! is_string($data['access_token'] ?? null) || ! is_numeric($data['expires'] ?? null)) {
            throw new VittlesException('La respuesta de autenticación no cumple el contrato observado.');
        }

        $this->token = $data['access_token'];
        $this->tokenExpiresAt = time() + max(1, (int) $data['expires']);
        $this->lastAuthentication = array_diff_key($data, ['access_token' => true]);
    }

    /**
     * Centraliza headers, tiempos, trazas y manejo de errores HTTP. Reintenta GET
     * ante fallos transitorios; nunca repite un POST de resultado incierto porque
     * el proveedor no garantiza idempotencia por client_ref.
     */
    private function send(string $method, string $path, array $data, array $headers = []): array
    {
        $maxAttempts = $method === 'GET' ? 3 : 1;
        $refreshed = false;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if ($this->token === null || time() >= $this->tokenExpiresAt - 5) {
                $this->authenticate();
            }

            $started = microtime(true);
            try {
                $pending = Http::acceptJson()->withToken($this->token)
                    ->withHeaders($headers)->timeout(6)->connectTimeout(2);
                $response = $method === 'GET'
                    ? $pending->get($this->url($path), $data)
                    : $pending->post($this->url($path), $data);
            } catch (ConnectionException) {
                $this->traces->record($method, $path, $method === 'GET' ? $data : [], $method === 'POST' ? $data : [], $headers, null, $this->elapsed($started), $attempt, 'Conexión interrumpida', $this->operationId, __METHOD__);
                if ($method === 'POST') {
                    throw new UnknownOutcome('Se perdió la respuesta del POST; su resultado es incierto.');
                }
                if ($attempt < $maxAttempts) {
                    usleep(200_000 * $attempt);

                    continue;
                }
                throw new VittlesException('Se perdió la conexión al consultar Vittles.');
            }
            $this->traces->record($method, $path, $method === 'GET' ? $data : [], $method === 'POST' ? $data : [], $headers, $response, $this->elapsed($started), $attempt, operationId: $this->operationId, source: __METHOD__);

            // Un 401 explícito indica que esta request no fue aceptada; renovar el token es seguro.
            if ($response->status() === 401 && ! $refreshed) {
                $this->token = null;
                $refreshed = true;
                $this->authenticate();
                $attempt--;

                continue;
            }

            // Solo las lecturas se repiten; 429 usa el header en milisegundos observado en el mock.
            if ($method === 'GET' && in_array($response->status(), [429, 500, 502, 503, 504], true) && $attempt < $maxAttempts) {
                $delayMs = $response->status() === 429
                    ? (int) $response->header('Retry-After-Ms', 4000)
                    : 200 * $attempt;
                usleep(min(4000, max(0, $delayMs)) * 1000);

                continue;
            }

            // Ante un POST no confirmado se informa incertidumbre y OrderService concilia por client_ref.
            if ($method === 'POST' && ($response->status() === 429 || $response->status() >= 500)) {
                throw new UnknownOutcome('Vittles no confirmó el resultado del POST (HTTP '.$response->status().').', $response->status());
            }

            if (! $response->successful()) {
                $body = $response->json();
                $reason = is_array($body) && is_string($body['error'] ?? null) ? ': '.$body['error'] : '';
                throw new VittlesException('Vittles respondió HTTP '.$response->status().$reason.'.', $response->status());
            }

            try {
                return $this->jsonObject($response);
            } catch (VittlesException $e) {
                if ($method === 'POST') {
                    throw new UnknownOutcome('El POST respondió con un cuerpo inesperado; la creación es incierta.');
                }

                throw $e;
            }
        }

        throw new VittlesException('Se agotaron los intentos de lectura de Vittles.');
    }

    /** Exige un objeto JSON para no interpretar arrays o cuerpos inválidos como éxito. */
    private function jsonObject(Response $response): array
    {
        $data = $response->json();
        if (! is_array($data) || array_is_list($data)) {
            throw new VittlesException('Vittles devolvió una respuesta JSON inesperada.');
        }

        return $data;
    }

    /** Compone la URL desde configuración para alternar mock y dobles de prueba. */
    private function url(string $path): string
    {
        return rtrim((string) config('vittles.base_url'), '/').$path;
    }

    /** Convierte la duración de un intento a milisegundos para las trazas. */
    private function elapsed(float $started): int
    {
        return (int) round((microtime(true) - $started) * 1000);
    }
}
