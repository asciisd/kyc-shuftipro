<?php

use Asciisd\KycShuftiPro\DTOs\ShuftiProRequest;
use Asciisd\KycShuftiPro\Exceptions\ShuftiProException;
use Asciisd\KycShuftiPro\Services\ShuftiProApiService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('shuftipro.api.client_id', 'test-client-id');
    Config::set('shuftipro.api.secret_key', 'test-secret-key');
    Config::set('shuftipro.api.base_url', 'https://api.shuftipro.com');
    Config::set('shuftipro.webhook.redirect_url', 'https://caveofx.test/kyc/verification/complete');
    Config::set('shuftipro.webhook.callback_url', 'https://caveofx.test/api/kyc/webhook');
});

it('includes reference parameter in redirect URL when creating verification', function () {
    Http::fake([
        'api.shuftipro.com/*' => Http::response([
            'reference' => 'test-ref-123',
            'event' => 'request.pending',
            'verification_url' => 'https://shuftipro.com/verify/test-ref-123',
        ], 200),
    ]);

    $service = new ShuftiProApiService;

    $request = new ShuftiProRequest(
        email: 'test@example.com',
        country: 'US',
        reference: 'test-ref-123'
    );

    $response = $service->createVerification($request);

    // Verify that the HTTP request was made with the correct redirect URL
    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);

        // Check that redirect_url includes the reference parameter
        expect($body['redirect_url'])->toBe('https://caveofx.test/kyc/verification/complete?reference=test-ref-123');

        return true;
    });

    expect($response->reference)->toBe('test-ref-123');
});

it('handles redirect URL with existing query parameters', function () {
    Config::set('shuftipro.webhook.redirect_url', 'https://caveofx.test/kyc/verification/complete?source=app');

    Http::fake([
        'api.shuftipro.com/*' => Http::response([
            'reference' => 'test-ref-456',
            'event' => 'request.pending',
            'verification_url' => 'https://shuftipro.com/verify/test-ref-456',
        ], 200),
    ]);

    $service = new ShuftiProApiService;

    $request = new ShuftiProRequest(
        email: 'test@example.com',
        country: 'US',
        reference: 'test-ref-456'
    );

    $response = $service->createVerification($request);

    // Verify that the HTTP request was made with the correct redirect URL
    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);

        // Check that redirect_url includes both existing and new parameters
        expect($body['redirect_url'])->toBe('https://caveofx.test/kyc/verification/complete?source=app&reference=test-ref-456');

        return true;
    });

    expect($response->reference)->toBe('test-ref-456');
});

it('retrieves verification with explicit credentials for cross-environment retrieval', function () {
    Http::fake([
        'https://api.external.shuftipro.com/status' => Http::response([
            'reference' => 'SP_ext_ref',
            'event' => 'verification.accepted',
            'extracted_data' => ['first_name' => 'Jane'],
            'verification_results' => ['document' => ['result' => 'approved']],
        ], 200),
    ]);

    $service = new ShuftiProApiService;

    $response = $service->retrieveWithCredentials(
        reference: 'SP_ext_ref',
        clientId: 'ext-client',
        secretKey: 'ext-secret',
        baseUrl: 'https://api.external.shuftipro.com',
    );

    expect($response->reference)->toBe('SP_ext_ref');
    expect($response->event)->toBe('verification.accepted');
    expect($response->extractedData)->toBe(['first_name' => 'Jane']);

    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);

        return $request->url() === 'https://api.external.shuftipro.com/status'
            && $body['reference'] === 'SP_ext_ref'
            && $body['get_images'] === 1
            && $request->hasHeader('Authorization');
    });
});

it('throws exception when external environment retrieval fails', function () {
    Http::fake([
        'https://api.external.shuftipro.com/status' => Http::response('Unauthorized', 401),
    ]);

    $service = new ShuftiProApiService;

    $service->retrieveWithCredentials(
        reference: 'SP_bad_ref',
        clientId: 'wrong-client',
        secretKey: 'wrong-secret',
        baseUrl: 'https://api.external.shuftipro.com',
    );
})->throws(ShuftiProException::class, 'Failed to retrieve verification from external environment');

it('uses custom redirect URL from request when provided', function () {
    Http::fake([
        'api.shuftipro.com/*' => Http::response([
            'reference' => 'test-ref-789',
            'event' => 'request.pending',
            'verification_url' => 'https://shuftipro.com/verify/test-ref-789',
        ], 200),
    ]);

    $service = new ShuftiProApiService;

    $request = new ShuftiProRequest(
        email: 'test@example.com',
        country: 'US',
        reference: 'test-ref-789',
        redirectUrl: 'https://custom.example.com/callback'
    );

    $response = $service->createVerification($request);

    // Verify that the HTTP request was made with the custom redirect URL
    Http::assertSent(function ($request) {
        $body = json_decode($request->body(), true);

        // Check that the custom redirect URL is used as-is
        expect($body['redirect_url'])->toBe('https://custom.example.com/callback');

        return true;
    });

    expect($response->reference)->toBe('test-ref-789');
});
