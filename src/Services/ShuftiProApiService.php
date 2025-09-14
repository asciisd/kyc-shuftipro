<?php

namespace Asciisd\KycShuftiPro\Services;

use Asciisd\KycShuftiPro\DTOs\ShuftiProRequest;
use Asciisd\KycShuftiPro\DTOs\ShuftiProResponse;
use Asciisd\KycShuftiPro\Exceptions\ShuftiProException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShuftiProApiService
{
    private string $baseUrl;

    private string $clientId;

    private string $secretKey;

    private int $timeout;

    private bool $loggingEnabled;

    private string $logChannel;

    public function __construct()
    {
        $clientId = config('shuftipro.api.client_id');
        $secretKey = config('shuftipro.api.secret_key');

        if (empty($clientId) || empty($secretKey)) {
            throw new ShuftiProException('ShuftiPro API credentials are not configured');
        }

        $this->baseUrl = config('shuftipro.api.base_url', 'https://api.shuftipro.com');
        $this->clientId = $clientId;
        $this->secretKey = $secretKey;
        $this->timeout = config('shuftipro.api.timeout', 30);
        $this->loggingEnabled = config('shuftipro.logging.enabled', true);
        $this->logChannel = config('shuftipro.logging.channel', 'daily');
    }

    /**
     * Create a verification request
     */
    public function createVerification(ShuftiProRequest $request): ShuftiProResponse
    {
        $payload = $this->buildVerificationPayload($request);

        $this->logActivity('Creating verification request', $payload);

        $response = Http::timeout($this->timeout)
            ->withBasicAuth($this->clientId, $this->secretKey)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->post($this->baseUrl, $payload);

        $this->logActivity('API Response', [
            'status' => $response->status(),
            'headers' => $response->headers(),
            'body' => $response->body()
        ]);

        if (! $response->successful()) {
            throw new ShuftiProException('Failed to create verification request: HTTP '.$response->status().' - '.$response->body());
        }

        $responseData = $response->json();
        $this->logActivity('Verification request response', $responseData);

        return ShuftiProResponse::fromApiResponse($responseData);
    }

    /**
     * Create a simple verification with minimal configuration
     */
    public function createSimpleVerification(string $email, string $country = ''): ShuftiProResponse
    {
        $request = new ShuftiProRequest(
            email: $email,
            country: $country,
            reference: $this->generateReference(),
            callbackUrl: $this->getCallbackUrl(),
            redirectUrl: $this->getRedirectUrl(),
        );

        return $this->createVerification($request);
    }

    /**
     * Retrieve verification data by reference
     */
    public function retrieveVerification(string $reference, bool $withImages = false): ShuftiProResponse
    {
        $this->logActivity('Retrieving verification data', ['reference' => $reference, 'with_images' => $withImages]);

        $payload = [
            'reference' => $reference,
        ];

        if ($withImages) {
            $payload['get_images'] = 1;
        }

        $response = Http::timeout($this->timeout)
            ->withBasicAuth($this->clientId, $this->secretKey)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->post($this->baseUrl.'/status', $payload);

        if (! $response->successful()) {
            throw new ShuftiProException('Failed to retrieve verification data: '.$response->body());
        }

        $responseData = $response->json();
        $this->logActivity('Verification status response', $responseData);

        return ShuftiProResponse::fromApiResponse($responseData);
    }

    /**
     * Build verification payload
     */
    private function buildVerificationPayload(ShuftiProRequest $request): array
    {
        $payload = [
            'reference' => $request->reference ?? $this->generateReference(),
            'email' => $request->email,
            'country' => $request->country,
            'language' => $request->language,
            'callback_url' => $request->callbackUrl ?? $this->getCallbackUrl(),
            'redirect_url' => $request->redirectUrl ?? $this->getRedirectUrl(),
            // Add required verification services
            'face' => [],
            'document' => [
                'supported_types' => ['passport', 'id_card', 'driving_license']
            ]
        ];

        if ($request->journeyId) {
            $payload['journey_id'] = $request->journeyId;
        }

        if ($request->allowedCountries) {
            $payload['allowed_countries'] = $request->allowedCountries;
        }

        if ($request->deniedCountries) {
            $payload['denied_countries'] = $request->deniedCountries;
        }

        if ($request->additionalData) {
            $payload = array_merge($payload, $request->additionalData);
        }

        return $payload;
    }

    /**
     * Generate unique reference
     */
    private function generateReference(): string
    {
        return 'SP_'.time().'_'.uniqid();
    }

    /**
     * Get callback URL
     */
    private function getCallbackUrl(): string
    {
        return config('shuftipro.webhook.callback_url', route('kyc.webhook.callback'));
    }

    /**
     * Get redirect URL
     */
    private function getRedirectUrl(): string
    {
        return config('shuftipro.webhook.redirect_url', route('kyc.verification.complete'));
    }

    /**
     * Log activity if logging is enabled
     */
    private function logActivity(string $message, array $data = []): void
    {
        if ($this->loggingEnabled) {
            Log::channel($this->logChannel)->info($message, $data);
        }
    }
}
