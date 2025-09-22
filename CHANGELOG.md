# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.4.0] - 2024-01-15

### Added

- **Journey Prioritization**: Automatic prioritization of journey verification over simple verification when journeys are enabled
- **Verification Type Differentiation**: Ability to differentiate between journey and simple verification from redirect URLs
- **Enhanced Redirect URLs**: Automatic addition of verification type parameters to redirect URLs
- **New API Methods**:
  - `createJourneyVerification()` method in ShuftiProApiService
  - `getRedirectUrlWithType()` method for type-specific redirect URLs
- **Improved Callback Handling**: Enhanced callback processing with verification type detection and storage
- **Type-Specific Messaging**: Different messages based on verification type (journey-based vs standard)
- **Data Tracking**: Automatic storage of verification type in KYC model data for analytics and reporting

### Enhanced

- **ShuftiProDriver**: Modified `createSimpleVerification()` to check journey configuration and prioritize accordingly
- **ShuftiProApiService**: Enhanced with journey-specific verification creation
- **Callback Processing**: Improved to handle and store verification type information
- **Frontend Integration**: Enhanced callback page with verification type badges and messaging

### Features

- **Configuration-Driven**: Controlled via `SHUFTIPRO_JOURNEYS_ENABLED` and `SHUFTIPRO_DEFAULT_JOURNEY_ID` environment variables
- **Backward Compatible**: Existing code continues to work without changes
- **Analytics Ready**: Verification type data stored for reporting and analysis
- **User Experience**: Type-specific messaging and visual indicators

### Technical Details

- Journey verification URLs include `type=journey` parameter
- Simple verification URLs include `type=simple` parameter
- Verification type stored in KYC model's data field
- Automatic fallback to simple verification when journeys are disabled or not configured

### Examples

- Added comprehensive examples in `examples/` directory:
  - `JourneyPrioritizationExample.php`: Demonstrates journey prioritization functionality
  - `VerificationTypeDifferentiation.php`: Shows how to differentiate verification types from redirects

## [1.3.0] - Previous Release

- Previous features and functionality
