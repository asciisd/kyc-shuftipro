<?php

/**
 * Example demonstrating how the KYC ShuftiPro package now prioritizes journeys
 * over simple verification when journeys are enabled.
 */

// Example configuration in your .env file:
// SHUFTIPRO_JOURNEYS_ENABLED=true
// SHUFTIPRO_DEFAULT_JOURNEY_ID=your_journey_id_here

// Example usage in your application:

use Asciisd\KycCore\Services\KycManager;
use App\Models\User;

class JourneyPrioritizationExample
{
    public function demonstrateJourneyPrioritization(KycManager $kycManager, User $user): void
    {
        // When SHUFTIPRO_JOURNEYS_ENABLED=true and SHUFTIPRO_DEFAULT_JOURNEY_ID is set:
        // The createSimpleVerification method will automatically use journey verification
        
        $options = [
            'country' => 'US',
            'language' => 'en',
        ];
        
        // This call will now:
        // 1. Check if journeys are enabled (config('shuftipro.idv_journeys.enabled'))
        // 2. Check if a default journey ID is configured (config('shuftipro.idv_journeys.default_journey_id'))
        // 3. If both conditions are met, use createJourneyVerification() instead of createSimpleVerification()
        // 4. If journeys are disabled or no journey ID is set, fall back to simple verification
        
        $response = $kycManager->createSimpleVerification($user, $options);
        
        echo "Verification created with reference: " . $response->reference . "\n";
        echo "Verification URL: " . $response->verificationUrl . "\n";
        
        // The API request will include the journey_id parameter when journeys are prioritized
        // This means users will go through your pre-configured ShuftiPro journey
        // instead of the default multi-service integration
    }
    
    public function configurationExamples(): array
    {
        return [
            'journey_enabled' => [
                'SHUFTIPRO_JOURNEYS_ENABLED' => true,
                'SHUFTIPRO_DEFAULT_JOURNEY_ID' => 'journey_123456',
                'result' => 'Uses journey verification with journey_id=journey_123456'
            ],
            'journey_disabled' => [
                'SHUFTIPRO_JOURNEYS_ENABLED' => false,
                'SHUFTIPRO_DEFAULT_JOURNEY_ID' => 'journey_123456',
                'result' => 'Uses simple multi-service verification (ignores journey_id)'
            ],
            'no_journey_id' => [
                'SHUFTIPRO_JOURNEYS_ENABLED' => true,
                'SHUFTIPRO_DEFAULT_JOURNEY_ID' => null,
                'result' => 'Uses simple multi-service verification (no journey_id available)'
            ],
        ];
    }
}

/**
 * Key Changes Made:
 * 
 * 1. Modified ShuftiProDriver::createSimpleVerification() to check journey configuration
 * 2. Added ShuftiProApiService::createJourneyVerification() method
 * 3. Journey verification is now prioritized when:
 *    - SHUFTIPRO_JOURNEYS_ENABLED=true
 *    - SHUFTIPRO_DEFAULT_JOURNEY_ID is not empty
 * 
 * Benefits:
 * - Seamless integration: existing code continues to work
 * - Configuration-driven: controlled via environment variables
 * - Backward compatible: falls back to simple verification when journeys are disabled
 * - User experience: users get the pre-configured journey flow when available
 */
