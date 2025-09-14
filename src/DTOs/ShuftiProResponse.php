<?php

namespace Asciisd\KycShuftiPro\DTOs;

class ShuftiProResponse
{
    public function __construct(
        public readonly string $reference,
        public readonly string $event,
        public readonly bool $success,
        public readonly ?string $verificationUrl = null,
        public readonly ?array $extractedData = null,
        public readonly ?array $verificationResults = null,
        public readonly ?array $documentImages = null,
        public readonly ?string $verificationVideo = null,
        public readonly ?string $verificationReport = null,
        public readonly ?string $imageAccessToken = null,
        public readonly ?string $country = null,
        public readonly ?bool $duplicateDetected = null,
        public readonly ?string $declineReason = null,
        public readonly ?array $rawResponse = null,
        public readonly ?string $message = null,
    ) {}

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function isPending(): bool
    {
        return in_array($this->event, ['request.pending', 'verification.pending']);
    }

    public function isCompleted(): bool
    {
        return in_array($this->event, ['verification.completed', 'verification.approved']);
    }

    public function isFailed(): bool
    {
        return in_array($this->event, ['verification.failed', 'verification.declined']);
    }

    public function getExtractedData(): ?array
    {
        return $this->extractedData;
    }

    public function getVerificationResults(): ?array
    {
        return $this->verificationResults;
    }

    public function getAllDocumentImageUrls(): ?array
    {
        return $this->documentImages;
    }

    public function getVerificationVideoUrl(): ?string
    {
        return $this->verificationVideo;
    }

    public function getVerificationReportUrl(): ?string
    {
        return $this->verificationReport;
    }

    public function getImageAccessToken(): ?string
    {
        return $this->imageAccessToken;
    }

    public function hasDuplicateAccount(): ?bool
    {
        return $this->duplicateDetected;
    }

    public function getDeclineReason(): ?string
    {
        return $this->declineReason;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function toArray(): array
    {
        return [
            'reference' => $this->reference,
            'event' => $this->event,
            'success' => $this->success,
            'verification_url' => $this->verificationUrl,
            'extracted_data' => $this->extractedData,
            'verification_results' => $this->verificationResults,
            'document_images' => $this->documentImages,
            'verification_video' => $this->verificationVideo,
            'verification_report' => $this->verificationReport,
            'image_access_token' => $this->imageAccessToken,
            'country' => $this->country,
            'duplicate_detected' => $this->duplicateDetected,
            'decline_reason' => $this->declineReason,
            'raw_response' => $this->rawResponse,
            'message' => $this->message,
        ];
    }

    /**
     * Create ShuftiProResponse from API response array
     */
    public static function fromApiResponse(array $response): self
    {
        return new self(
            reference: $response['reference'] ?? '',
            event: $response['event'] ?? 'unknown',
            success: $response['result']['event'] === 'verification.completed' ?? false,
            verificationUrl: $response['verification_url'] ?? null,
            extractedData: $response['extracted_data'] ?? null,
            verificationResults: $response['verification_results'] ?? null,
            documentImages: $response['document_images'] ?? null,
            verificationVideo: $response['verification_video'] ?? null,
            verificationReport: $response['verification_report'] ?? null,
            imageAccessToken: $response['image_access_token'] ?? null,
            country: $response['country'] ?? null,
            duplicateDetected: $response['duplicate_detected'] ?? null,
            declineReason: $response['decline_reason'] ?? null,
            rawResponse: $response,
            message: $response['message'] ?? null,
        );
    }
}
