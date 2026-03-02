## KYC ShuftiPro (asciisd/kyc-shuftipro)

ShuftiPro identity verification driver for `asciisd/kyc-core`. Provides hosted journey-based verification flows, webhook handling with signature verification, document download/storage, and data transformation via the ShuftiPro API.

### Key Classes

- `ShuftiProDriver` — Implements `KycDriverInterface`. Supports document verification, face verification, address verification, journey verification, webhook callbacks, and document download.
- `ShuftiProApiService` — HTTP client for ShuftiPro API: create verifications, journey verifications, retrieve status.
- `ShuftiProWebhookService` — Handles incoming webhooks and validates signatures.
- `ShuftiProDocumentService` — Downloads and stores verification documents to configured storage disk.
- `ShuftiProTransformer` — Implements `KycDataTransformerInterface`. Maps ShuftiPro extracted data to `StandardizedKycData`.

### Verification (Journey-Based Flow)

ShuftiPro uses hosted journey pages. `createSimpleVerification()` creates a journey verification when journeys are enabled, returning a `KycVerificationResponse` with a `verificationUrl`. The result arrives via webhook.

@verbatim
<code-snippet name="Simple Verification" lang="php">
use Asciisd\KycCore\Facades\Kyc;

$response = Kyc::createSimpleVerification($user, [
    'country' => 'US',
    'language' => 'en',
]);

if ($response->hasVerificationUrl()) {
    return redirect($response->verificationUrl);
}
</code-snippet>
@endverbatim

### Events (from kyc-core)

- `VerificationCompleted` — Dispatched when ShuftiPro reports `verification.accepted`.
- `VerificationFailed` — Dispatched when ShuftiPro reports `verification.declined`.

### Status Mapping

| ShuftiPro Event | KycStatusEnum |
|-----------------|---------------|
| `verification.accepted` | `Completed` |
| `verification.declined` | `Rejected` |
| `request.pending` | `RequestPending` |
| `request.received` | `InProgress` |
| `review.pending` | `ReviewPending` |
| `request.timeout` | `RequestTimeout` |
| `verification.cancelled` | `VerificationCancelled` |

### Config

Config file: `config/shuftipro.php`. Key values: `api.client_id`, `api.secret_key`, `api.base_url`, `webhook.secret_key`, `webhook.callback_url`, `webhook.redirect_url`, `idv_journeys.default_journey_id`, `idv_journeys.enabled`, `documents.auto_download`, `documents.storage_disk`.

Publish config: `php artisan vendor:publish --tag=shuftipro-config`
