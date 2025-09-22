<?php

/**
 * Example demonstrating how to differentiate between journey and simple verification
 * from link redirections in the KYC ShuftiPro package.
 */

class VerificationTypeDifferentiation
{
    /**
     * How the differentiation works:
     * 
     * 1. When creating verification requests, the system automatically adds a 'type' parameter
     *    to the redirect URL based on the verification method used.
     * 
     * 2. Journey verification URLs include: ?reference=SP_123&type=journey
     * 3. Simple verification URLs include: ?reference=SP_123&type=simple
     * 
     * 4. When users complete verification and are redirected back to your application,
     *    you can detect the verification type from the URL parameters.
     */

    public function exampleRedirectUrls(): array
    {
        return [
            'journey_verification' => [
                'url' => 'https://caveofx.test/kyc/callback?reference=SP_1234567890_journey&type=journey&status=approved',
                'parameters' => [
                    'reference' => 'SP_1234567890_journey',
                    'type' => 'journey',
                    'status' => 'approved'
                ],
                'description' => 'User completed journey-based verification successfully'
            ],
            'simple_verification' => [
                'url' => 'https://caveofx.test/kyc/callback?reference=SP_1234567890_simple&type=simple&status=approved',
                'parameters' => [
                    'reference' => 'SP_1234567890_simple',
                    'type' => 'simple',
                    'status' => 'approved'
                ],
                'description' => 'User completed standard multi-service verification successfully'
            ],
        ];
    }

    /**
     * Example of how the callback handler processes different verification types
     */
    public function callbackHandlingExample(): array
    {
        // This is what happens in your KycController::callback method:
        
        return [
            'journey_callback' => [
                'input' => [
                    'reference' => 'SP_1234567890_journey',
                    'status' => 'approved',
                    'type' => 'journey'
                ],
                'processing' => [
                    'verification_type_detected' => 'journey',
                    'message_generated' => 'Your journey-based identity verification has been approved!',
                    'data_stored' => [
                        'verification_type' => 'journey',
                        'callback_received_at' => '2024-01-15T10:30:00Z'
                    ]
                ],
                'frontend_props' => [
                    'status' => 'approved',
                    'reference' => 'SP_1234567890_journey',
                    'verificationType' => 'journey',
                    'message' => 'Your journey-based identity verification has been approved!'
                ]
            ],
            'simple_callback' => [
                'input' => [
                    'reference' => 'SP_1234567890_simple',
                    'status' => 'approved',
                    'type' => 'simple'
                ],
                'processing' => [
                    'verification_type_detected' => 'simple',
                    'message_generated' => 'Your standard identity verification has been approved!',
                    'data_stored' => [
                        'verification_type' => 'simple',
                        'callback_received_at' => '2024-01-15T10:30:00Z'
                    ]
                ],
                'frontend_props' => [
                    'status' => 'approved',
                    'reference' => 'SP_1234567890_simple',
                    'verificationType' => 'simple',
                    'message' => 'Your standard identity verification has been approved!'
                ]
            ]
        ];
    }

    /**
     * Frontend display differences based on verification type
     */
    public function frontendDisplayExamples(): array
    {
        return [
            'journey_display' => [
                'badge' => [
                    'text' => 'Journey-based',
                    'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
                ],
                'message' => 'Your journey-based identity verification has been approved!',
                'description' => 'User went through a pre-configured ShuftiPro journey'
            ],
            'simple_display' => [
                'badge' => [
                    'text' => 'Standard',
                    'class' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
                ],
                'message' => 'Your standard identity verification has been approved!',
                'description' => 'User went through multi-service integration (document + face verification)'
            ]
        ];
    }

    /**
     * How to query verification type from database after callback
     */
    public function databaseQueryExample(): string
    {
        return '
        // Get verification type from KYC data
        $user = auth()->user();
        $kyc = $user->kyc;
        
        if ($kyc && isset($kyc->data["verification_type"])) {
            $verificationType = $kyc->data["verification_type"];
            
            switch ($verificationType) {
                case "journey":
                    // Handle journey verification logic
                    $this->handleJourneyVerification($user, $kyc);
                    break;
                    
                case "simple":
                    // Handle simple verification logic
                    $this->handleSimpleVerification($user, $kyc);
                    break;
                    
                default:
                    // Handle unknown verification type
                    $this->handleUnknownVerification($user, $kyc);
                    break;
            }
        }
        ';
    }

    /**
     * Analytics and reporting use cases
     */
    public function analyticsExamples(): array
    {
        return [
            'verification_type_distribution' => [
                'query' => 'SELECT verification_type, COUNT(*) as count FROM kycs WHERE data->>"$.verification_type" IS NOT NULL GROUP BY verification_type',
                'purpose' => 'Track which verification method is used more frequently'
            ],
            'success_rate_by_type' => [
                'query' => 'SELECT verification_type, status, COUNT(*) as count FROM kycs WHERE data->>"$.verification_type" IS NOT NULL GROUP BY verification_type, status',
                'purpose' => 'Compare success rates between journey and simple verification'
            ],
            'completion_time_analysis' => [
                'query' => 'SELECT verification_type, AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as avg_minutes FROM kycs WHERE completed_at IS NOT NULL GROUP BY verification_type',
                'purpose' => 'Analyze which verification type completes faster'
            ]
        ];
    }
}

/**
 * Key Benefits of Verification Type Differentiation:
 * 
 * 1. **User Experience Tracking**: Know which verification flow users experienced
 * 2. **Analytics & Reporting**: Compare performance between journey and simple verification
 * 3. **Debugging & Support**: Quickly identify which verification method was used for support cases
 * 4. **A/B Testing**: Test different verification approaches and measure results
 * 5. **Compliance & Auditing**: Track verification methods for regulatory requirements
 * 6. **Personalized Messaging**: Show different messages based on verification type
 * 
 * Implementation Summary:
 * - Redirect URLs automatically include 'type' parameter
 * - Callback handler detects and stores verification type
 * - Frontend displays appropriate badges and messages
 * - Database stores verification type for future reference
 * - Analytics can differentiate between verification methods
 */
