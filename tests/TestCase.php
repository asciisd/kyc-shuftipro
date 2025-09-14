<?php

namespace Asciisd\KycShuftiPro\Tests;

use Asciisd\KycCore\Providers\KycServiceProvider;
use Asciisd\KycShuftiPro\Providers\ShuftiProServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup ShuftiPro configuration for testing
        $this->app['config']->set('shuftipro', [
            'api' => [
                'client_id' => 'test_client_id',
                'secret_key' => 'test_secret_key',
                'base_url' => 'https://api.shuftipro.com',
                'timeout' => 30,
            ],
            'webhook' => [
                'secret_key' => 'test_webhook_secret',
                'callback_url' => 'https://test.example.com/callback',
                'redirect_url' => 'https://test.example.com/redirect',
                'signature_validation' => true,
            ],
            'idv_journeys' => [
                'default_journey_id' => 'test_journey_id',
                'enabled' => true,
            ],
            'logging' => [
                'enabled' => false, // Disable logging in tests
                'channel' => 'daily',
                'level' => 'info',
            ],
            'documents' => [
                'auto_download' => true,
                'storage_disk' => 'local',
                'storage_path' => 'shuftipro/documents',
                'allowed_types' => ['jpg', 'jpeg', 'png', 'pdf', 'mp4'],
                'max_file_size' => 10485760,
            ],
            'verification' => [
                'default_country' => 'US',
                'default_language' => 'en',
                'allowed_countries' => 'US,GB,CA',
                'denied_countries' => 'IR,KP',
                'enable_duplicate_detection' => true,
                'verification_timeout' => 3600,
            ],
        ]);

        // Setup KYC configuration with ShuftiPro driver
        $this->app['config']->set('kyc', [
            'default_driver' => 'shuftipro',
            'drivers' => [
                'shuftipro' => [
                    'name' => 'ShuftiPro',
                    'description' => 'ShuftiPro Identity Verification Service',
                    'enabled' => true,
                    'class' => \Asciisd\KycShuftiPro\Drivers\ShuftiProDriver::class,
                    'supports' => [
                        'document_verification' => true,
                        'face_verification' => true,
                        'address_verification' => true,
                        'background_checks' => true,
                        'age_verification' => true,
                        'journey_verification' => true,
                        'direct_api' => true,
                        'webhook_callbacks' => true,
                        'document_download' => true,
                    ],
                ],
            ],
            'settings' => [
                'require_email_verification' => false,
                'max_verification_attempts' => 3,
                'verification_url_expiry_hours' => 24,
                'auto_download_documents' => true,
                'document_storage_disk' => 'local',
                'document_storage_path' => 'kyc/documents',
                'enable_duplicate_detection' => true,
                'webhook_signature_validation' => true,
            ],
            'supported_countries' => ['US', 'GB', 'CA'],
            'restricted_countries' => ['IR', 'KP'],
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            KycServiceProvider::class,
            ShuftiProServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
