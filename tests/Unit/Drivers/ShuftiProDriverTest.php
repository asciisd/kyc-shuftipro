<?php

namespace Asciisd\KycShuftiPro\Tests\Unit\Drivers;

use Asciisd\KycCore\DTOs\KycVerificationRequest;
use Asciisd\KycCore\DTOs\KycVerificationResponse;
use Asciisd\KycShuftiPro\Drivers\ShuftiProDriver;
use Asciisd\KycShuftiPro\DTOs\ShuftiProRequest;
use Asciisd\KycShuftiPro\DTOs\ShuftiProResponse;
use Asciisd\KycShuftiPro\Services\ShuftiProApiService;
use Asciisd\KycShuftiPro\Services\ShuftiProDocumentService;
use Asciisd\KycShuftiPro\Services\ShuftiProWebhookService;
use Asciisd\KycShuftiPro\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Mockery;

class ShuftiProDriverTest extends TestCase
{
    private ShuftiProDriver $driver;

    private Mockery\MockInterface $apiService;

    private Mockery\MockInterface $documentService;

    private Mockery\MockInterface $webhookService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiService = Mockery::mock(ShuftiProApiService::class);
        $this->documentService = Mockery::mock(ShuftiProDocumentService::class);
        $this->webhookService = Mockery::mock(ShuftiProWebhookService::class);

        $this->driver = new ShuftiProDriver(
            $this->apiService,
            $this->documentService,
            $this->webhookService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_verification()
    {
        $user = $this->createTestUser();
        $request = new KycVerificationRequest(
            email: 'test@example.com',
            country: 'US',
            language: 'en'
        );

        $shuftiProResponse = new ShuftiProResponse(
            reference: 'sp_ref_123',
            event: 'request.pending',
            success: true,
            verificationUrl: 'https://shuftipro.verification.url',
            extractedData: ['name' => 'John Doe'],
            verificationResults: ['document' => 'verified'],
            documentImages: ['front.jpg', 'back.jpg'],
            country: 'US',
            message: 'Verification request created successfully'
        );

        $this->apiService
            ->shouldReceive('createVerification')
            ->once()
            ->with(Mockery::type(ShuftiProRequest::class))
            ->andReturn($shuftiProResponse);

        $response = $this->driver->createVerification($user, $request);

        $this->assertInstanceOf(KycVerificationResponse::class, $response);
        $this->assertEquals('sp_ref_123', $response->reference);
        $this->assertEquals('request.pending', $response->event);
        $this->assertTrue($response->success);
        $this->assertEquals('https://shuftipro.verification.url', $response->verificationUrl);
        $this->assertEquals(['name' => 'John Doe'], $response->extractedData);
        $this->assertEquals(['document' => 'verified'], $response->verificationResults);
        $this->assertEquals(['front.jpg', 'back.jpg'], $response->documentImages);
        $this->assertEquals('US', $response->country);
        $this->assertEquals('Verification request created successfully', $response->message);
    }

    public function test_create_simple_verification()
    {
        $user = $this->createTestUser();
        $options = ['country' => 'US'];

        $shuftiProResponse = new ShuftiProResponse(
            reference: 'sp_ref_456',
            event: 'request.pending',
            success: true,
            verificationUrl: 'https://shuftipro.verification.url'
        );

        $this->apiService
            ->shouldReceive('createSimpleVerification')
            ->once()
            ->with('test@example.com', 'US')
            ->andReturn($shuftiProResponse);

        $response = $this->driver->createSimpleVerification($user, $options);

        $this->assertInstanceOf(KycVerificationResponse::class, $response);
        $this->assertEquals('sp_ref_456', $response->reference);
        $this->assertTrue($response->success);
    }

    public function test_retrieve_verification()
    {
        $reference = 'sp_ref_123';
        $shuftiProResponse = new ShuftiProResponse(
            reference: $reference,
            event: 'verification.completed',
            success: true,
            extractedData: ['name' => 'John Doe'],
            documentImages: ['front.jpg']
        );

        $this->apiService
            ->shouldReceive('retrieveVerification')
            ->once()
            ->with($reference, true)
            ->andReturn($shuftiProResponse);

        $response = $this->driver->retrieveVerification($reference);

        $this->assertInstanceOf(KycVerificationResponse::class, $response);
        $this->assertEquals($reference, $response->reference);
        $this->assertEquals('verification.completed', $response->event);
        $this->assertTrue($response->success);
    }

    public function test_can_resume_verification()
    {
        $reference = 'sp_ref_123';
        $shuftiProResponse = new ShuftiProResponse(
            reference: $reference,
            event: 'request.pending',
            success: true
        );

        $this->apiService
            ->shouldReceive('retrieveVerification')
            ->once()
            ->with($reference, false)
            ->andReturn($shuftiProResponse);

        $canResume = $this->driver->canResumeVerification($reference);

        $this->assertTrue($canResume);
    }

    public function test_can_resume_verification_returns_false_on_exception()
    {
        $reference = 'sp_ref_123';

        $this->apiService
            ->shouldReceive('retrieveVerification')
            ->once()
            ->with($reference, false)
            ->andThrow(new \Exception('API Error'));

        $canResume = $this->driver->canResumeVerification($reference);

        $this->assertFalse($canResume);
    }

    public function test_get_verification_url()
    {
        $reference = 'sp_ref_123';
        $shuftiProResponse = new ShuftiProResponse(
            reference: $reference,
            event: 'request.pending',
            success: true,
            verificationUrl: 'https://shuftipro.verification.url'
        );

        $this->apiService
            ->shouldReceive('retrieveVerification')
            ->once()
            ->with($reference, false)
            ->andReturn($shuftiProResponse);

        $url = $this->driver->getVerificationUrl($reference);

        $this->assertEquals('https://shuftipro.verification.url', $url);
    }

    public function test_get_verification_url_returns_null_on_exception()
    {
        $reference = 'sp_ref_123';

        $this->apiService
            ->shouldReceive('retrieveVerification')
            ->once()
            ->with($reference, false)
            ->andThrow(new \Exception('API Error'));

        $url = $this->driver->getVerificationUrl($reference);

        $this->assertNull($url);
    }

    public function test_process_webhook()
    {
        $payload = [
            'reference' => 'sp_ref_123',
            'event' => 'verification.completed',
            'result' => [
                'event' => 'verification.completed',
            ],
        ];
        $headers = ['HTTP_X_SHUFTIPRO_SIGNATURE' => 'signature'];

        $shuftiProResponse = new ShuftiProResponse(
            reference: 'sp_ref_123',
            event: 'verification.completed',
            success: true,
            extractedData: ['name' => 'John Doe']
        );

        $this->webhookService
            ->shouldReceive('handleWebhook')
            ->once()
            ->with($payload, $headers)
            ->andReturn($shuftiProResponse);

        $response = $this->driver->processWebhook($payload, $headers);

        $this->assertInstanceOf(KycVerificationResponse::class, $response);
        $this->assertEquals('sp_ref_123', $response->reference);
        $this->assertEquals('verification.completed', $response->event);
        $this->assertTrue($response->success);
    }

    public function test_validate_webhook_signature()
    {
        $payload = ['reference' => 'sp_ref_123'];
        $headers = ['HTTP_X_SHUFTIPRO_SIGNATURE' => 'signature'];

        $this->webhookService
            ->shouldReceive('validateSignature')
            ->once()
            ->with($payload, $headers)
            ->andReturn(true);

        $isValid = $this->driver->validateWebhookSignature($payload, $headers);

        $this->assertTrue($isValid);
    }

    public function test_download_documents()
    {
        $user = $this->createTestUser();
        $reference = 'sp_ref_123';
        $documents = ['front.jpg', 'back.jpg', 'selfie.jpg'];

        $this->documentService
            ->shouldReceive('downloadAndStoreDocuments')
            ->once()
            ->with($user, $reference)
            ->andReturn($documents);

        $result = $this->driver->downloadDocuments($user, $reference);

        $this->assertEquals($documents, $result);
    }

    public function test_get_config()
    {
        $config = $this->driver->getConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('api', $config);
        $this->assertArrayHasKey('webhook', $config);
        $this->assertArrayHasKey('idv_journeys', $config);
    }

    public function test_get_name()
    {
        $name = $this->driver->getName();

        $this->assertEquals('shuftipro', $name);
    }

    public function test_is_enabled()
    {
        $isEnabled = $this->driver->isEnabled();

        $this->assertTrue($isEnabled);
    }

    public function test_get_capabilities()
    {
        $capabilities = $this->driver->getCapabilities();

        $this->assertIsArray($capabilities);
        $this->assertTrue($capabilities['document_verification']);
        $this->assertTrue($capabilities['face_verification']);
        $this->assertTrue($capabilities['webhook_callbacks']);
        $this->assertTrue($capabilities['document_download']);
    }

    public function test_convert_kyc_request_to_shufti_pro_request()
    {
        $user = $this->createTestUser();
        $request = new KycVerificationRequest(
            email: 'test@example.com',
            country: 'US',
            language: 'en',
            redirectUrl: 'https://example.com/redirect',
            callbackUrl: 'https://example.com/callback',
            reference: 'custom_ref_123',
            journeyId: 'journey_456',
            allowedCountries: ['US', 'CA'],
            deniedCountries: ['IR', 'KP'],
            additionalData: ['custom' => 'value']
        );

        $shuftiProResponse = new ShuftiProResponse(
            reference: 'sp_ref_123',
            event: 'request.pending',
            success: true
        );

        $this->apiService
            ->shouldReceive('createVerification')
            ->once()
            ->with(Mockery::on(function (ShuftiProRequest $shuftiProRequest) {
                return $shuftiProRequest->email === 'test@example.com'
                    && $shuftiProRequest->country === 'US'
                    && $shuftiProRequest->language === 'en'
                    && $shuftiProRequest->redirectUrl === 'https://example.com/redirect'
                    && $shuftiProRequest->callbackUrl === 'https://example.com/callback'
                    && $shuftiProRequest->reference === 'custom_ref_123'
                    && $shuftiProRequest->journeyId === 'journey_456'
                    && $shuftiProRequest->allowedCountries === ['US', 'CA']
                    && $shuftiProRequest->deniedCountries === ['IR', 'KP']
                    && $shuftiProRequest->additionalData === ['custom' => 'value'];
            }))
            ->andReturn($shuftiProResponse);

        $this->driver->createVerification($user, $request);

        // If we get here without exceptions, the conversion worked correctly
        $this->assertTrue(true);
    }

    private function createTestUser(): Model
    {
        return new class extends Model
        {
            public $email = 'test@example.com';

            public function getKey()
            {
                return 1;
            }

            public function getMorphClass()
            {
                return 'User';
            }
        };
    }
}
