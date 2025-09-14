<?php

namespace Asciisd\KycShuftiPro\Drivers;

use Asciisd\KycCore\Contracts\KycDriverInterface;
use Asciisd\KycCore\DTOs\KycVerificationRequest;
use Asciisd\KycCore\DTOs\KycVerificationResponse;
use Asciisd\KycShuftiPro\DTOs\ShuftiProRequest;
use Asciisd\KycShuftiPro\DTOs\ShuftiProResponse;
use Asciisd\KycShuftiPro\Services\ShuftiProApiService;
use Asciisd\KycShuftiPro\Services\ShuftiProDocumentService;
use Asciisd\KycShuftiPro\Services\ShuftiProWebhookService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class ShuftiProDriver implements KycDriverInterface
{
    public function __construct(
        private readonly ShuftiProApiService $apiService,
        private readonly ShuftiProDocumentService $documentService,
        private readonly ShuftiProWebhookService $webhookService,
    ) {}

    public function createVerification(Model $user, KycVerificationRequest $request): KycVerificationResponse
    {
        // Convert generic request to ShuftiPro-specific request
        $shuftiProRequest = $this->convertToShuftiProRequest($request);

        // Create verification via API
        $response = $this->apiService->createVerification($shuftiProRequest);

        return $this->convertToKycResponse($response);
    }

    public function createSimpleVerification(Model $user, array $options = []): KycVerificationResponse
    {
        $country = $options['country'] ?? '';
        $response = $this->apiService->createSimpleVerification($user->email, $country);

        return $this->convertToKycResponse($response);
    }

    public function retrieveVerification(string $reference): KycVerificationResponse
    {
        $withImages = true; // Always retrieve with images for complete data
        $response = $this->apiService->retrieveVerification($reference, $withImages);

        return $this->convertToKycResponse($response);
    }

    public function canResumeVerification(string $reference): bool
    {
        try {
            $response = $this->apiService->retrieveVerification($reference, false);

            return $response->isPending();
        } catch (\Exception) {
            return false;
        }
    }

    public function getVerificationUrl(string $reference): ?string
    {
        try {
            $response = $this->apiService->retrieveVerification($reference, false);

            return $response->verificationUrl;
        } catch (\Exception) {
            return null;
        }
    }

    public function processWebhook(array $payload, array $headers = []): KycVerificationResponse
    {
        $response = $this->webhookService->handleWebhook($payload, $headers);

        return $this->convertToKycResponse($response);
    }

    public function validateWebhookSignature(array $payload, array $headers): bool
    {
        return $this->webhookService->validateSignature($payload, $headers);
    }

    public function downloadDocuments(Model $user, string $reference): array
    {
        return $this->documentService->downloadAndStoreDocuments($user, $reference);
    }

    public function getConfig(): array
    {
        return Config::get('shuftipro', []);
    }

    public function getName(): string
    {
        return 'shuftipro';
    }

    public function isEnabled(): bool
    {
        return Config::get('kyc.drivers.shuftipro.enabled', false);
    }

    public function getCapabilities(): array
    {
        return Config::get('kyc.drivers.shuftipro.supports', []);
    }

    /**
     * Convert KycVerificationRequest to ShuftiProRequest
     */
    private function convertToShuftiProRequest(KycVerificationRequest $request): ShuftiProRequest
    {
        return new ShuftiProRequest(
            email: $request->email,
            country: $request->country ?? '',
            language: $request->language ?? 'en',
            redirectUrl: $request->redirectUrl,
            callbackUrl: $request->callbackUrl,
            reference: $request->reference,
            journeyId: $request->journeyId,
            allowedCountries: $request->allowedCountries,
            deniedCountries: $request->deniedCountries,
            additionalData: $request->additionalData,
        );
    }

    /**
     * Convert ShuftiProResponse to KycVerificationResponse
     */
    private function convertToKycResponse(ShuftiProResponse $shuftiProResponse): KycVerificationResponse
    {
        return new KycVerificationResponse(
            reference: $shuftiProResponse->reference,
            event: $shuftiProResponse->event,
            success: $shuftiProResponse->isSuccessful(),
            verificationUrl: $shuftiProResponse->verificationUrl ?? null,
            extractedData: $shuftiProResponse->getExtractedData() ?? null,
            verificationResults: $shuftiProResponse->getVerificationResults() ?? null,
            documentImages: $shuftiProResponse->getAllDocumentImageUrls() ?? null,
            verificationVideo: $shuftiProResponse->getVerificationVideoUrl() ?? null,
            verificationReport: $shuftiProResponse->getVerificationReportUrl() ?? null,
            imageAccessToken: $shuftiProResponse->getImageAccessToken() ?? null,
            country: $shuftiProResponse->country ?? null,
            duplicateDetected: $shuftiProResponse->hasDuplicateAccount() ?? null,
            declineReason: $shuftiProResponse->getDeclineReason() ?? null,
            rawResponse: $shuftiProResponse->toArray(),
            message: $shuftiProResponse->getMessage() ?? null,
        );
    }
}
