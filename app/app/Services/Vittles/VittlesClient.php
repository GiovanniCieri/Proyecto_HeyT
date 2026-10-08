<?php

namespace App\Services\Vittles;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class VittlesClient
{
    private ?string $token = null;

    private int $tokenExpiresAt = 0;

    public function get(string $path, array $query = []): array
    {
        return $this->send('GET', $path, $query);
    }

    public function createOrder(array $payload, string $locationId): array
    {
        return $this->send('POST', '/v1/orders', $payload, ['X-Vittles-Location' => $locationId]);
    }

    private function authenticate(): void
    {
        $id = config('vittles.client_id');
        $secret = config('vittles.client_secret');

        if (! is_string($id) || $id === '' || ! is_string($secret) || $secret === '') {
            throw new VittlesException('Faltan VITTLES_CLIENT_ID o VITTLES_CLIENT_SECRET en .env.');
        }

        try {
            $response = Http::acceptJson()->timeout(6)->connectTimeout(2)->post($this->url('/oauth/token'), [
                'client_id' => $id,
                'client_secret' => $secret,
            ]);
        } catch (ConnectionException) {
            throw new VittlesException('No se pudo conectar con Vittles para autenticarse.');
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
    }

    private function send(string $method, string $path, array $data, array $headers = []): array
    {
        $maxAttempts = $method === 'GET' ? 3 : 1;
        $refreshed = false;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if ($this->token === null || time() >= $this->tokenExpiresAt - 5) {
                $this->authenticate();
            }

            try {
                $pending = Http::acceptJson()->withToken($this->token)
                    ->withHeaders($headers)->timeout(6)->connectTimeout(2);
                $response = $method === 'GET'
                    ? $pending->get($this->url($path), $data)
                    : $pending->post($this->url($path), $data);
            } catch (ConnectionException) {
                if ($method === 'POST') {
                    throw new UnknownOutcome('Se perdió la respuesta del POST; su resultado es incierto.');
                }
                if ($attempt < $maxAttempts) {
                    usleep(200_000 * $attempt);

                    continue;
                }
                throw new VittlesException('Se perdió la conexión al consultar Vittles.');
            }

            if ($response->status() === 401 && ! $refreshed) {
                $this->token = null;
                $refreshed = true;
                $this->authenticate();
                $attempt--;

                continue;
            }

            if ($method === 'GET' && in_array($response->status(), [429, 500, 502, 503, 504], true) && $attempt < $maxAttempts) {
                $delayMs = $response->status() === 429
                    ? (int) $response->header('Retry-After-Ms', 4000)
                    : 200 * $attempt;
                usleep(min(4000, max(0, $delayMs)) * 1000);

                continue;
            }

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

    private function jsonObject(Response $response): array
    {
        $data = $response->json();
        if (! is_array($data) || array_is_list($data)) {
            throw new VittlesException('Vittles devolvió una respuesta JSON inesperada.');
        }

        return $data;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('vittles.base_url'), '/').$path;
    }
}
