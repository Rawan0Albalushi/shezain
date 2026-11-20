<?php

namespace App\Services\Thawani;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class ThawaniClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $checkoutUrl,
        private readonly string $secretKey,
        private readonly string $publishableKey,
    ) {}

    public function isConfigured(): bool
    {
        return $this->secretKey !== '' && $this->publishableKey !== '';
    }

    public function isSandbox(): bool
    {
        return str_contains($this->checkoutUrl, 'uatcheckout.thawani.om');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createSession(array $payload): array
    {
        $this->assertConfigured();

        try {
            $response = Http::baseUrl($this->baseUrl)
                ->withHeaders(['thawani-api-key' => $this->secretKey])
                ->acceptJson()
                ->asJson()
                ->connectTimeout(5)
                ->timeout(20)
                ->post('/checkout/session', $payload);
        } catch (ConnectionException $exception) {
            throw new ThawaniException('Thawani could not be reached.', ['message' => $exception->getMessage()]);
        }

        return $this->data($response->status(), $response->json() ?? [], 'Thawani could not start the payment.');
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveSession(string $sessionId): array
    {
        $this->assertConfigured();

        try {
            $response = Http::baseUrl($this->baseUrl)
                ->withHeaders(['thawani-api-key' => $this->secretKey])
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->retry(2, 250, function ($exception) {
                    return $exception instanceof ConnectionException
                        || ($exception instanceof RequestException && ($exception->response->serverError() || $exception->response->status() === 429));
                })
                ->get('/checkout/session/'.$sessionId);
        } catch (ConnectionException $exception) {
            throw new ThawaniException('Thawani could not be reached.', ['message' => $exception->getMessage()]);
        }

        return $this->data($response->status(), $response->json() ?? [], 'Thawani could not confirm the payment.');
    }

    public function checkoutUrl(string $sessionId): string
    {
        return rtrim($this->checkoutUrl, '/').'/pay/'.$sessionId.'?key='.urlencode($this->publishableKey);
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new ThawaniException('Thawani keys are not configured.');
        }
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function data(int $status, array $body, string $fallback): array
    {
        if ($status < 200 || $status >= 300 || ($body['success'] ?? false) !== true || ! is_array($body['data'] ?? null)) {
            throw new ThawaniException((string) ($body['description'] ?? $fallback), $body);
        }

        return $body['data'];
    }
}
