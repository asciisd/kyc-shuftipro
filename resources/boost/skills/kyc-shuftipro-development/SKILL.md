---
name: kyc-shuftipro-development
description: "Build and integrate ShuftiPro identity verification flows with the asciisd/kyc-shuftipro package. Activates when working with ShuftiProDriver, ShuftiProApiService, ShuftiProWebhookService, ShuftiProDocumentService, ShuftiProTransformer, ShuftiPro journey verification, ShuftiPro webhook handling, ShuftiPro document download, or configuring shuftipro.php."
---

# KYC ShuftiPro Development

## Package Overview

`asciisd/kyc-shuftipro` is a ShuftiPro identity verification driver for `asciisd/kyc-core`. It provides hosted journey-based verification flows, webhook handling with signature verification, document download/storage, and data transformation via the ShuftiPro API.

**Namespace:** `Asciisd\KycShuftiPro`
**Requires:** `asciisd/kyc-core ^1.3`

## Architecture

```
src/
├── Drivers/
│   └── ShuftiProDriver.php            — KycDriverInterface implementation
├── DTOs/
│   ├── ShuftiProRequest.php           — API request DTO
│   └── ShuftiProResponse.php          — API response DTO
├── Exceptions/
│   └── ShuftiProException.php         — ShuftiPro-specific exceptions
├── Providers/
│   └── ShuftiProServiceProvider.php   — Service provider (config, bindings)
├── Services/
│   ├── ShuftiProApiService.php        — HTTP client for ShuftiPro API
│   ├── ShuftiProDocumentService.php   — Document download and storage
│   └── ShuftiProWebhookService.php    — Webhook handling and signature validation
└── Transformers/
    └── ShuftiProTransformer.php       — Maps ShuftiPro data → StandardizedKycData
```

## When to Use This Skill

Use this skill when:
- Creating or modifying ShuftiPro verification integrations
- Working with ShuftiPro journey-based verification flows
- Handling ShuftiPro webhook events
- Configuring `shuftipro.php`
- Working with ShuftiPro document download/storage
- Implementing ShuftiPro data transformers
- Debugging ShuftiPro verification or webhook issues
- Writing tests for ShuftiPro verification flows

## Verification Flow

ShuftiPro uses a **hosted journey page** model:

1. Call `Kyc::createSimpleVerification($user)` → creates verification via ShuftiPro API
2. Returns `KycVerificationResponse` with `verificationUrl`
3. Redirect user to the ShuftiPro hosted verification page
4. User completes identity verification on ShuftiPro's page
5. ShuftiPro sends webhook to `api/kyc/webhook`
6. `KycWebhookController` (from kyc-core) routes to `ShuftiProDriver::processWebhook()`
7. `ShuftiProWebhookService` validates signature and parses response
8. KYC model status and data are updated, events dispatched

## Creating Verifications

### Simple Verification (Recommended)

Uses journey verification when `idv_journeys.enabled` is true:

```php
use Asciisd\KycCore\Facades\Kyc;

$response = Kyc::createSimpleVerification($user, [
    'country' => 'US',
    'language' => 'en',
]);

if ($response->hasVerificationUrl()) {
    return redirect($response->verificationUrl);
}
```

### Full Verification

```php
use Asciisd\KycCore\DTOs\KycVerificationRequest;
use Asciisd\KycCore\Facades\Kyc;

$request = new KycVerificationRequest(
    email: $user->email,
    country: 'US',
    language: 'en',
    redirectUrl: route('kyc.complete'),
    callbackUrl: route('kyc.webhook'),
    journeyId: config('shuftipro.idv_journeys.default_journey_id'),
    allowedCountries: ['US', 'GB', 'CA'],
);

$response = Kyc::createVerification($user, $request);
```

### Journey Verification (Direct API)

```php
use Asciisd\KycShuftiPro\Services\ShuftiProApiService;

$apiService = app(ShuftiProApiService::class);
$response = $apiService->createJourneyVerification(
    email: $user->email,
    country: 'US',
    language: 'en',
    journeyId: config('shuftipro.idv_journeys.default_journey_id'),
);
```

## ShuftiProDriver

Implements all methods of `KycDriverInterface`:

| Method | Description |
|--------|-------------|
| `createVerification()` | Creates full verification with `KycVerificationRequest` |
| `createSimpleVerification()` | Creates journey or direct verification using defaults |
| `retrieveVerification()` | Retrieves verification status from ShuftiPro API |
| `canResumeVerification()` | Checks if incomplete verification can be resumed |
| `getVerificationUrl()` | Gets hosted verification page URL for a reference |
| `processWebhook()` | Parses and processes incoming ShuftiPro webhook |
| `validateWebhookSignature()` | Validates webhook signature using secret key |
| `downloadDocuments()` | Downloads and stores verification documents |
| `mapEventToStatus()` | Maps ShuftiPro event strings to `KycStatusEnum` |

## ShuftiPro Event → Status Mapping

| ShuftiPro Event | KycStatusEnum |
|-----------------|---------------|
| `verification.accepted` | `Completed` |
| `verification.declined` | `Rejected` |
| `request.pending` | `RequestPending` |
| `request.received` | `InProgress` |
| `review.pending` | `ReviewPending` |
| `request.timeout` | `RequestTimeout` |
| `verification.cancelled` | `VerificationCancelled` |
| `request.invalid` | `VerificationFailed` |

## ShuftiProResponse Properties

| Property | Type | Description |
|----------|------|-------------|
| `reference` | `string` | ShuftiPro reference ID |
| `event` | `string` | Event type (e.g., `verification.accepted`) |
| `success` | `bool` | Whether verification succeeded |
| `verificationUrl` | `?string` | Hosted verification page URL |
| `extractedData` | `?array` | Extracted identity data |
| `verificationResults` | `?array` | Per-service verification results |
| `documentImages` | `?array` | Document image URLs |
| `verificationVideo` | `?string` | Verification video URL |
| `verificationReport` | `?string` | Verification report URL |
| `imageAccessToken` | `?string` | Token for accessing images |
| `country` | `?string` | Detected country |
| `duplicateDetected` | `?bool` | Duplicate account flag |
| `declineReason` | `?string` | Reason for decline |

Methods: `isSuccessful()`, `isPending()`, `isCompleted()`, `isFailed()`, `getAllDocumentImageUrls()`, `hasDuplicateAccount()`, `getDeclineReason()`.

Static: `fromApiResponse(array $response)` — creates instance from raw ShuftiPro API response.

## ShuftiProTransformer

Maps ShuftiPro extracted data to `StandardizedKycData` format:

```php
use Asciisd\KycShuftiPro\Transformers\ShuftiProTransformer;

$transformer = new ShuftiProTransformer();

if ($transformer->canHandle($rawData)) {
    $standardized = $transformer->transform($rawData);
    // Returns array matching StandardizedKycData fields
}
```

Detection: `canHandle()` checks for `proofs` key or `verification_data` in raw data to identify ShuftiPro payloads.

## Document Download

`ShuftiProDocumentService` handles downloading and storing verification documents:

```php
use Asciisd\KycShuftiPro\Services\ShuftiProDocumentService;

$docService = app(ShuftiProDocumentService::class);
$documents = $docService->downloadAndStoreDocuments($user, $reference);

// Check if document exists
$exists = $docService->documentExists($filePath);

// Get document URL
$url = $docService->getDocumentUrl($filePath);
```

Storage uses the disk configured in `shuftipro.documents.storage_disk` and path from `shuftipro.documents.storage_path`.

## Webhook Signature Validation

ShuftiPro webhooks are validated using a SHA256 signature:

```php
use Asciisd\KycShuftiPro\Services\ShuftiProWebhookService;

$webhookService = app(ShuftiProWebhookService::class);
$isValid = $webhookService->validateSignature($payload, $headers);
```

Signature validation can be toggled via `shuftipro.webhook.signature_validation`.

## Configuration

Config file: `config/shuftipro.php`

| Key | Env Variable | Default | Description |
|-----|-------------|---------|-------------|
| `api.client_id` | `SHUFTIPRO_CLIENT_ID` | — | API client ID |
| `api.secret_key` | `SHUFTIPRO_SECRET_KEY` | — | API secret key |
| `api.base_url` | `SHUFTIPRO_BASE_URL` | `https://api.shuftipro.com` | API base URL |
| `api.timeout` | `SHUFTIPRO_TIMEOUT` | `30` | HTTP timeout |
| `webhook.secret_key` | `SHUFTIPRO_WEBHOOK_SECRET` | — | Webhook secret key |
| `webhook.callback_url` | `SHUFTIPRO_CALLBACK_URL` | — | Webhook callback URL |
| `webhook.redirect_url` | `SHUFTIPRO_REDIRECT_URL` | — | Post-verification redirect URL |
| `webhook.signature_validation` | `SHUFTIPRO_WEBHOOK_SIGNATURE_VALIDATION` | `true` | Enable signature verification |
| `idv_journeys.default_journey_id` | `SHUFTIPRO_DEFAULT_JOURNEY_ID` | — | Default journey ID |
| `idv_journeys.enabled` | `SHUFTIPRO_JOURNEYS_ENABLED` | `true` | Enable journey-based verification |
| `documents.auto_download` | `SHUFTIPRO_AUTO_DOWNLOAD_DOCUMENTS` | `true` | Auto-download documents |
| `documents.storage_disk` | `SHUFTIPRO_DOCUMENT_STORAGE_DISK` | `s3` | Storage disk for documents |
| `documents.storage_path` | `SHUFTIPRO_DOCUMENT_STORAGE_PATH` | `shuftipro/documents` | Storage path prefix |
| `logging.enabled` | `SHUFTIPRO_LOGGING_ENABLED` | `true` | Enable logging |
| `logging.channel` | `SHUFTIPRO_LOG_CHANNEL` | `daily` | Log channel |
| `verification.default_country` | `SHUFTIPRO_DEFAULT_COUNTRY` | — | Default country code |
| `verification.default_language` | `SHUFTIPRO_DEFAULT_LANGUAGE` | `en` | Default language |

Publish config: `php artisan vendor:publish --tag=shuftipro-config`

## Driver Registration

ShuftiPro is registered as a driver in `config/kyc.php` (from kyc-core):

```php
'drivers' => [
    'shuftipro' => [
        'name' => 'ShuftiPro',
        'description' => 'ShuftiPro Identity Verification Service',
        'enabled' => env('SHUFTIPRO_ENABLED', true),
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
```

## Capabilities

| Capability | Supported |
|-----------|-----------|
| Document Verification | Yes |
| Face Verification | Yes |
| Address Verification | Yes |
| Background Checks | Yes |
| Age Verification | Yes |
| Journey Verification | Yes |
| Direct API | Yes |
| Webhook Callbacks | Yes |
| Document Download | Yes |
