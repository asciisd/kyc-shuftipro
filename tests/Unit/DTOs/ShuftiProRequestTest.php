<?php

namespace Asciisd\KycShuftiPro\Tests\Unit\DTOs;

use Asciisd\KycShuftiPro\DTOs\ShuftiProRequest;
use Asciisd\KycShuftiPro\Tests\TestCase;

class ShuftiProRequestTest extends TestCase
{
    public function test_can_create_request_with_minimal_data()
    {
        $request = new ShuftiProRequest(email: 'test@example.com');

        $this->assertEquals('test@example.com', $request->email);
        $this->assertEquals('', $request->country);
        $this->assertEquals('en', $request->language);
        $this->assertNull($request->redirectUrl);
        $this->assertNull($request->callbackUrl);
        $this->assertNull($request->reference);
        $this->assertNull($request->journeyId);
        $this->assertNull($request->allowedCountries);
        $this->assertNull($request->deniedCountries);
        $this->assertNull($request->additionalData);
    }

    public function test_can_create_request_with_all_data()
    {
        $request = new ShuftiProRequest(
            email: 'test@example.com',
            country: 'US',
            language: 'en',
            redirectUrl: 'https://example.com/redirect',
            callbackUrl: 'https://example.com/callback',
            reference: 'test_ref_123',
            journeyId: 'journey_456',
            allowedCountries: ['US', 'CA'],
            deniedCountries: ['IR', 'KP'],
            additionalData: ['custom' => 'value']
        );

        $this->assertEquals('test@example.com', $request->email);
        $this->assertEquals('US', $request->country);
        $this->assertEquals('en', $request->language);
        $this->assertEquals('https://example.com/redirect', $request->redirectUrl);
        $this->assertEquals('https://example.com/callback', $request->callbackUrl);
        $this->assertEquals('test_ref_123', $request->reference);
        $this->assertEquals('journey_456', $request->journeyId);
        $this->assertEquals(['US', 'CA'], $request->allowedCountries);
        $this->assertEquals(['IR', 'KP'], $request->deniedCountries);
        $this->assertEquals(['custom' => 'value'], $request->additionalData);
    }

    public function test_to_array_returns_correct_data()
    {
        $request = new ShuftiProRequest(
            email: 'test@example.com',
            country: 'US',
            language: 'en',
            redirectUrl: 'https://example.com/redirect',
            callbackUrl: 'https://example.com/callback',
            reference: 'test_ref_123',
            journeyId: 'journey_456',
            allowedCountries: ['US', 'CA'],
            deniedCountries: ['IR', 'KP'],
            additionalData: ['custom' => 'value']
        );

        $array = $request->toArray();

        $expected = [
            'email' => 'test@example.com',
            'country' => 'US',
            'language' => 'en',
            'redirect_url' => 'https://example.com/redirect',
            'callback_url' => 'https://example.com/callback',
            'reference' => 'test_ref_123',
            'journey_id' => 'journey_456',
            'allowed_countries' => ['US', 'CA'],
            'denied_countries' => ['IR', 'KP'],
            'additional_data' => ['custom' => 'value'],
        ];

        $this->assertEquals($expected, $array);
    }

    public function test_to_array_filters_null_values()
    {
        $request = new ShuftiProRequest(
            email: 'test@example.com',
            country: 'US'
        );

        $array = $request->toArray();

        $expected = [
            'email' => 'test@example.com',
            'country' => 'US',
            'language' => 'en',
        ];

        $this->assertEquals($expected, $array);
        $this->assertArrayNotHasKey('redirect_url', $array);
        $this->assertArrayNotHasKey('callback_url', $array);
        $this->assertArrayNotHasKey('reference', $array);
        $this->assertArrayNotHasKey('journey_id', $array);
        $this->assertArrayNotHasKey('allowed_countries', $array);
        $this->assertArrayNotHasKey('denied_countries', $array);
        $this->assertArrayNotHasKey('additional_data', $array);
    }

    public function test_readonly_properties_cannot_be_modified()
    {
        $request = new ShuftiProRequest(email: 'test@example.com');

        // This should not throw an error during instantiation
        // But attempting to modify would cause a fatal error
        $this->assertEquals('test@example.com', $request->email);
    }
}
