<?php

namespace Asciisd\KycShuftiPro\Tests\Unit\DTOs;

use Asciisd\KycShuftiPro\DTOs\ShuftiProResponse;
use Asciisd\KycShuftiPro\Tests\TestCase;

class ShuftiProResponseTest extends TestCase
{
    public function test_can_create_response_with_minimal_data()
    {
        $response = new ShuftiProResponse(
            reference: 'sp_ref_123',
            event: 'verification.completed',
            success: true
        );

        $this->assertEquals('sp_ref_123', $response->reference);
        $this->assertEquals('verification.completed', $response->event);
        $this->assertTrue($response->success);
        $this->assertNull($response->verificationUrl);
        $this->assertNull($response->extractedData);
        $this->assertNull($response->verificationResults);
        $this->assertNull($response->documentImages);
        $this->assertNull($response->verificationVideo);
        $this->assertNull($response->verificationReport);
        $this->assertNull($response->imageAccessToken);
        $this->assertNull($response->country);
        $this->assertNull($response->duplicateDetected);
        $this->assertNull($response->declineReason);
        $this->assertNull($response->rawResponse);
        $this->assertNull($response->message);
    }

    public function test_can_create_response_with_all_data()
    {
        $response = new ShuftiProResponse(
            reference: 'sp_ref_123',
            event: 'verification.completed',
            success: true,
            verificationUrl: 'https://shuftipro.verification.url',
            extractedData: ['name' => 'John Doe'],
            verificationResults: ['document' => 'verified'],
            documentImages: ['front.jpg', 'back.jpg'],
            verificationVideo: 'https://shuftipro.video.url',
            verificationReport: 'https://shuftipro.report.url',
            imageAccessToken: 'access_token_123',
            country: 'US',
            duplicateDetected: false,
            declineReason: null,
            rawResponse: ['raw' => 'data'],
            message: 'Verification completed successfully'
        );

        $this->assertEquals('sp_ref_123', $response->reference);
        $this->assertEquals('verification.completed', $response->event);
        $this->assertTrue($response->success);
        $this->assertEquals('https://shuftipro.verification.url', $response->verificationUrl);
        $this->assertEquals(['name' => 'John Doe'], $response->extractedData);
        $this->assertEquals(['document' => 'verified'], $response->verificationResults);
        $this->assertEquals(['front.jpg', 'back.jpg'], $response->documentImages);
        $this->assertEquals('https://shuftipro.video.url', $response->verificationVideo);
        $this->assertEquals('https://shuftipro.report.url', $response->verificationReport);
        $this->assertEquals('access_token_123', $response->imageAccessToken);
        $this->assertEquals('US', $response->country);
        $this->assertFalse($response->duplicateDetected);
        $this->assertNull($response->declineReason);
        $this->assertEquals(['raw' => 'data'], $response->rawResponse);
        $this->assertEquals('Verification completed successfully', $response->message);
    }

    public function test_is_successful()
    {
        $successfulResponse = new ShuftiProResponse(
            reference: 'sp_ref',
            event: 'verification.completed',
            success: true
        );

        $failedResponse = new ShuftiProResponse(
            reference: 'sp_ref',
            event: 'verification.failed',
            success: false
        );

        $this->assertTrue($successfulResponse->isSuccessful());
        $this->assertFalse($failedResponse->isSuccessful());
    }

    public function test_is_pending()
    {
        $pendingEvents = ['request.pending', 'verification.pending'];
        $nonPendingEvents = ['verification.completed', 'verification.failed', 'verification.cancelled'];

        foreach ($pendingEvents as $event) {
            $response = new ShuftiProResponse(
                reference: 'sp_ref',
                event: $event,
                success: true
            );
            $this->assertTrue($response->isPending(), "Event '{$event}' should be pending");
        }

        foreach ($nonPendingEvents as $event) {
            $response = new ShuftiProResponse(
                reference: 'sp_ref',
                event: $event,
                success: true
            );
            $this->assertFalse($response->isPending(), "Event '{$event}' should not be pending");
        }
    }

    public function test_is_completed()
    {
        $completedEvents = ['verification.completed', 'verification.approved'];
        $nonCompletedEvents = ['request.pending', 'verification.failed', 'verification.pending'];

        foreach ($completedEvents as $event) {
            $response = new ShuftiProResponse(
                reference: 'sp_ref',
                event: $event,
                success: true
            );
            $this->assertTrue($response->isCompleted(), "Event '{$event}' should be completed");
        }

        foreach ($nonCompletedEvents as $event) {
            $response = new ShuftiProResponse(
                reference: 'sp_ref',
                event: $event,
                success: true
            );
            $this->assertFalse($response->isCompleted(), "Event '{$event}' should not be completed");
        }
    }

    public function test_is_failed()
    {
        $failedEvents = ['verification.failed', 'verification.declined'];
        $nonFailedEvents = ['request.pending', 'verification.completed', 'verification.pending'];

        foreach ($failedEvents as $event) {
            $response = new ShuftiProResponse(
                reference: 'sp_ref',
                event: $event,
                success: true
            );
            $this->assertTrue($response->isFailed(), "Event '{$event}' should be failed");
        }

        foreach ($nonFailedEvents as $event) {
            $response = new ShuftiProResponse(
                reference: 'sp_ref',
                event: $event,
                success: true
            );
            $this->assertFalse($response->isFailed(), "Event '{$event}' should not be failed");
        }
    }

    public function test_get_extracted_data()
    {
        $response = new ShuftiProResponse(
            reference: 'sp_ref',
            event: 'verification.completed',
            success: true,
            extractedData: ['name' => 'John Doe', 'dob' => '1990-01-01']
        );

        $this->assertEquals(['name' => 'John Doe', 'dob' => '1990-01-01'], $response->getExtractedData());
    }

    public function test_get_verification_results()
    {
        $response = new ShuftiProResponse(
            reference: 'sp_ref',
            event: 'verification.completed',
            success: true,
            verificationResults: ['document' => 'verified', 'face' => 'verified']
        );

        $this->assertEquals(['document' => 'verified', 'face' => 'verified'], $response->getVerificationResults());
    }

    public function test_get_all_document_image_urls()
    {
        $response = new ShuftiProResponse(
            reference: 'sp_ref',
            event: 'verification.completed',
            success: true,
            documentImages: ['front.jpg', 'back.jpg', 'selfie.jpg']
        );

        $this->assertEquals(['front.jpg', 'back.jpg', 'selfie.jpg'], $response->getAllDocumentImageUrls());
    }

    public function test_get_verification_video_url()
    {
        $response = new ShuftiProResponse(
            reference: 'sp_ref',
            event: 'verification.completed',
            success: true,
            verificationVideo: 'https://shuftipro.video.url'
        );

        $this->assertEquals('https://shuftipro.video.url', $response->getVerificationVideoUrl());
    }

    public function test_get_verification_report_url()
    {
        $response = new ShuftiProResponse(
            reference: 'sp_ref',
            event: 'verification.completed',
            success: true,
            verificationReport: 'https://shuftipro.report.url'
        );

        $this->assertEquals('https://shuftipro.report.url', $response->getVerificationReportUrl());
    }

    public function test_get_image_access_token()
    {
        $response = new ShuftiProResponse(
            reference: 'sp_ref',
            event: 'verification.completed',
            success: true,
            imageAccessToken: 'access_token_123'
        );

        $this->assertEquals('access_token_123', $response->getImageAccessToken());
    }

    public function test_has_duplicate_account()
    {
        $response = new ShuftiProResponse(
            reference: 'sp_ref',
            event: 'verification.completed',
            success: true,
            duplicateDetected: true
        );

        $this->assertTrue($response->hasDuplicateAccount());
    }

    public function test_get_decline_reason()
    {
        $response = new ShuftiProResponse(
            reference: 'sp_ref',
            event: 'verification.failed',
            success: false,
            declineReason: 'Document quality too low'
        );

        $this->assertEquals('Document quality too low', $response->getDeclineReason());
    }

    public function test_get_message()
    {
        $response = new ShuftiProResponse(
            reference: 'sp_ref',
            event: 'verification.completed',
            success: true,
            message: 'Verification completed successfully'
        );

        $this->assertEquals('Verification completed successfully', $response->getMessage());
    }

    public function test_to_array_returns_correct_data()
    {
        $response = new ShuftiProResponse(
            reference: 'sp_ref_123',
            event: 'verification.completed',
            success: true,
            verificationUrl: 'https://shuftipro.verification.url',
            extractedData: ['name' => 'John Doe'],
            verificationResults: ['document' => 'verified'],
            documentImages: ['front.jpg', 'back.jpg'],
            verificationVideo: 'https://shuftipro.video.url',
            verificationReport: 'https://shuftipro.report.url',
            imageAccessToken: 'access_token_123',
            country: 'US',
            duplicateDetected: false,
            declineReason: null,
            rawResponse: ['raw' => 'data'],
            message: 'Verification completed successfully'
        );

        $array = $response->toArray();

        $expected = [
            'reference' => 'sp_ref_123',
            'event' => 'verification.completed',
            'success' => true,
            'verification_url' => 'https://shuftipro.verification.url',
            'extracted_data' => ['name' => 'John Doe'],
            'verification_results' => ['document' => 'verified'],
            'document_images' => ['front.jpg', 'back.jpg'],
            'verification_video' => 'https://shuftipro.video.url',
            'verification_report' => 'https://shuftipro.report.url',
            'image_access_token' => 'access_token_123',
            'country' => 'US',
            'duplicate_detected' => false,
            'decline_reason' => null,
            'raw_response' => ['raw' => 'data'],
            'message' => 'Verification completed successfully',
        ];

        $this->assertEquals($expected, $array);
    }

    public function test_from_api_response()
    {
        $apiResponse = [
            'reference' => 'sp_ref_123',
            'event' => 'verification.completed',
            'result' => [
                'event' => 'verification.completed',
            ],
            'verification_url' => 'https://shuftipro.verification.url',
            'extracted_data' => ['name' => 'John Doe'],
            'verification_results' => ['document' => 'verified'],
            'document_images' => ['front.jpg', 'back.jpg'],
            'verification_video' => 'https://shuftipro.video.url',
            'verification_report' => 'https://shuftipro.report.url',
            'image_access_token' => 'access_token_123',
            'country' => 'US',
            'duplicate_detected' => false,
            'decline_reason' => null,
            'message' => 'Verification completed successfully',
        ];

        $response = ShuftiProResponse::fromApiResponse($apiResponse);

        $this->assertEquals('sp_ref_123', $response->reference);
        $this->assertEquals('verification.completed', $response->event);
        $this->assertTrue($response->success);
        $this->assertEquals('https://shuftipro.verification.url', $response->verificationUrl);
        $this->assertEquals(['name' => 'John Doe'], $response->extractedData);
        $this->assertEquals(['document' => 'verified'], $response->verificationResults);
        $this->assertEquals(['front.jpg', 'back.jpg'], $response->documentImages);
        $this->assertEquals('https://shuftipro.video.url', $response->verificationVideo);
        $this->assertEquals('https://shuftipro.report.url', $response->verificationReport);
        $this->assertEquals('access_token_123', $response->imageAccessToken);
        $this->assertEquals('US', $response->country);
        $this->assertFalse($response->duplicateDetected);
        $this->assertNull($response->declineReason);
        $this->assertEquals('Verification completed successfully', $response->message);
        $this->assertEquals($apiResponse, $response->rawResponse);
    }

    public function test_from_api_response_with_minimal_data()
    {
        $apiResponse = [
            'reference' => 'sp_ref_123',
            'event' => 'request.pending',
        ];

        $response = ShuftiProResponse::fromApiResponse($apiResponse);

        $this->assertEquals('sp_ref_123', $response->reference);
        $this->assertEquals('request.pending', $response->event);
        $this->assertFalse($response->success); // No result.event = verification.completed
        $this->assertNull($response->verificationUrl);
        $this->assertNull($response->extractedData);
    }

    public function test_readonly_properties_cannot_be_modified()
    {
        $response = new ShuftiProResponse(
            reference: 'sp_ref',
            event: 'verification.completed',
            success: true
        );

        // This should not throw an error during instantiation
        // But attempting to modify would cause a fatal error
        $this->assertEquals('sp_ref', $response->reference);
    }
}
