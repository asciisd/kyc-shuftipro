<?php

namespace Asciisd\KycShuftiPro\Services;

use Asciisd\KycShuftiPro\DTOs\ShuftiProResponse;
use Asciisd\KycShuftiPro\Exceptions\ShuftiProException;
use Illuminate\Support\Facades\Log;

class ShuftiProWebhookService
{
    private string $webhookSecret;

    private bool $loggingEnabled;

    private string $logChannel;

    public function __construct()
    {
        $this->webhookSecret = config('shuftipro.webhook.secret_key');
        $this->loggingEnabled = config('shuftipro.logging.enabled', true);
        $this->logChannel = config('shuftipro.logging.channel', 'daily');

        if (empty($this->webhookSecret)) {
            throw new ShuftiProException('ShuftiPro webhook secret is not configured');
        }
    }

    /**
     * Handle incoming webhook
     */
    public function handleWebhook(array $payload, array $headers = []): ShuftiProResponse
    {
        $this->logActivity('Webhook received', $payload);

        // Validate signature if enabled
        if (config('kyc.settings.webhook_signature_validation', true)) {
            if (! $this->validateSignature($payload, $headers)) {
                throw new ShuftiProException('Invalid webhook signature');
            }
        }

        return ShuftiProResponse::fromApiResponse($payload);
    }

    /**
     * Validate webhook signature
     */
    public function validateSignature(array $payload, array $headers): bool
    {
        $receivedSignature = $this->extractSignatureFromHeaders($headers);

        if (! $receivedSignature) {
            $this->logActivity('No signature found in headers', $headers);

            return false;
        }

        $calculatedSignature = $this->calculateSignature($payload);

        $isValid = hash_equals($calculatedSignature, $receivedSignature);

        if (! $isValid) {
            $this->logActivity('Invalid webhook signature', [
                'received' => $receivedSignature,
                'calculated' => $calculatedSignature,
            ]);
        }

        return $isValid;
    }

    /**
     * Extract signature from headers
     */
    private function extractSignatureFromHeaders(array $headers): ?string
    {
        // Check for common signature header names
        $signatureHeaders = [
            'HTTP_X_SHUFTIPRO_SIGNATURE',
            'HTTP_X_WEBHOOK_SIGNATURE',
            'HTTP_SIGNATURE',
            'X-ShuftiPro-Signature',
            'X-Webhook-Signature',
            'Signature',
        ];

        foreach ($signatureHeaders as $header) {
            if (isset($headers[$header])) {
                return $headers[$header];
            }
        }

        return null;
    }

    /**
     * Calculate signature for payload
     */
    private function calculateSignature(array $payload): string
    {
        // Remove signature from payload if it exists
        unset($payload['signature']);

        // Sort payload keys
        ksort($payload);

        // Build signature string
        $signatureString = '';
        foreach ($payload as $key => $value) {
            $signatureString .= $key.'='.(is_array($value) ? json_encode($value) : $value).'&';
        }

        $signatureString = rtrim($signatureString, '&');
        $signatureString .= $this->webhookSecret;

        return hash('sha256', $signatureString);
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
