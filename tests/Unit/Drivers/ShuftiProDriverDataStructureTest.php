<?php

use Asciisd\KycShuftiPro\Drivers\ShuftiProDriver;
use Asciisd\KycShuftiPro\DTOs\ShuftiProResponse;
use Asciisd\KycShuftiPro\Services\ShuftiProApiService;
use Asciisd\KycShuftiPro\Services\ShuftiProDocumentService;
use Asciisd\KycShuftiPro\Services\ShuftiProWebhookService;

it('does not create nested raw_response duplication when converting response', function () {
    // Mock the services
    $apiService = Mockery::mock(ShuftiProApiService::class);
    $documentService = Mockery::mock(ShuftiProDocumentService::class);
    $webhookService = Mockery::mock(ShuftiProWebhookService::class);

    $driver = new ShuftiProDriver($apiService, $documentService, $webhookService);

    // Create a realistic webhook payload
    $webhookPayload = [
        'event' => 'request.pending',
        'country' => null,
        'message' => null,
        'success' => true,
        'reference' => 'SP_TEST_12345',
        'verification_url' => 'https://app.shuftipro.com/verification/process/test123',
        'decline_reason' => null,
        'extracted_data' => null,
        'document_images' => null,
        'duplicate_detected' => null,
        'image_access_token' => null,
        'verification_video' => null,
        'verification_report' => null,
        'verification_results' => null,
    ];

    // Create ShuftiProResponse from webhook payload
    $shuftiProResponse = ShuftiProResponse::fromApiResponse($webhookPayload);

    // Use reflection to access the private convertToKycResponse method
    $reflection = new ReflectionClass($driver);
    $method = $reflection->getMethod('convertToKycResponse');
    $method->setAccessible(true);

    // Convert to KycVerificationResponse
    $kycResponse = $method->invoke($driver, $shuftiProResponse);
    $kycArray = $kycResponse->toArray();

    // Assertions to prevent data duplication
    expect($kycArray)->toHaveKey('raw_response');
    expect($kycArray['raw_response'])->toBeArray();
    
    // The critical test: raw_response should NOT contain another raw_response
    expect($kycArray['raw_response'])->not->toHaveKey('raw_response');
    
    // raw_response should contain the original API data
    expect($kycArray['raw_response'])->toEqual($webhookPayload);
    
    // Verify the structure is clean
    expect($kycArray['raw_response'])->toHaveKeys([
        'event', 'country', 'message', 'success', 'reference', 'verification_url'
    ]);
    
    // Ensure no nested duplication exists
    $rawResponseKeys = array_keys($kycArray['raw_response']);
    foreach ($rawResponseKeys as $key) {
        if (is_array($kycArray['raw_response'][$key])) {
            expect($kycArray['raw_response'][$key])->not->toHaveKey('raw_response');
        }
    }
});

it('maintains data integrity after conversion', function () {
    $apiService = Mockery::mock(ShuftiProApiService::class);
    $documentService = Mockery::mock(ShuftiProDocumentService::class);
    $webhookService = Mockery::mock(ShuftiProWebhookService::class);

    $driver = new ShuftiProDriver($apiService, $documentService, $webhookService);

    $webhookPayload = [
        'event' => 'verification.completed',
        'reference' => 'SP_COMPLETE_123',
        'success' => true,
        'verification_url' => 'https://app.shuftipro.com/verification/process/complete123',
        'extracted_data' => ['name' => 'John Doe'],
        'verification_results' => ['document' => 'valid'],
    ];

    $shuftiProResponse = ShuftiProResponse::fromApiResponse($webhookPayload);

    $reflection = new ReflectionClass($driver);
    $method = $reflection->getMethod('convertToKycResponse');
    $method->setAccessible(true);

    $kycResponse = $method->invoke($driver, $shuftiProResponse);
    $kycArray = $kycResponse->toArray();

    // Verify all data is preserved correctly
    expect($kycArray['reference'])->toBe('SP_COMPLETE_123');
    expect($kycArray['event'])->toBe('verification.completed');
    expect($kycArray['success'])->toBeTrue();
    expect($kycArray['verification_url'])->toBe('https://app.shuftipro.com/verification/process/complete123');
    expect($kycArray['extracted_data'])->toBe(['name' => 'John Doe']);
    expect($kycArray['verification_results'])->toBe(['document' => 'valid']);
    
    // Verify raw_response contains original payload without nesting
    expect($kycArray['raw_response'])->toBe($webhookPayload);
    expect($kycArray['raw_response'])->not->toHaveKey('raw_response');
});
