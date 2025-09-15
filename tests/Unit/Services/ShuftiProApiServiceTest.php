<?php

use Asciisd\KycShuftiPro\DTOs\ShuftiProRequest;
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

    $service = new ShuftiProApiService();
    
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

    $service = new ShuftiProApiService();
    
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

it('uses custom redirect URL from request when provided', function () {
    Http::fake([
        'api.shuftipro.com/*' => Http::response([
            'reference' => 'test-ref-789',
            'event' => 'request.pending',
            'verification_url' => 'https://shuftipro.com/verify/test-ref-789',
        ], 200),
    ]);

    $service = new ShuftiProApiService();
    
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
