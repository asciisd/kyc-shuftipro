<?php

declare(strict_types=1);

namespace Asciisd\KycShuftiPro\Transformers;

use Asciisd\KycCore\Contracts\KycDataTransformerInterface;
use Asciisd\KycCore\DTOs\StandardizedKycData;

class ShuftiProTransformer implements KycDataTransformerInterface
{
    public function transform(array $rawData): array
    {
        $standardizedData = new StandardizedKycData(
            firstName: $this->extractFirstName($rawData),
            middleName: $this->extractMiddleName($rawData),
            lastName: $this->extractLastName($rawData),
            dateOfBirth: $this->extractDateOfBirth($rawData),
            gender: $this->extractGender($rawData),
            nationality: $this->extractNationality($rawData),
            country: $this->extractCountry($rawData),
            placeOfBirth: $this->extractPlaceOfBirth($rawData),
            address: $this->extractAddress($rawData),
            city: $this->extractCity($rawData),
            state: $this->extractState($rawData),
            postalCode: $this->extractPostalCode($rawData),
            phoneNumber: $this->extractPhoneNumber($rawData),
            email: $this->extractEmail($rawData),
            documents: $this->extractDocuments($rawData),
            additionalData: $this->extractAdditionalData($rawData),
        );

        return $standardizedData->toArray();
    }

    public function canHandle(array $rawData): bool
    {
        return isset($rawData['verification_data'])
            || isset($rawData['event'])
            || isset($rawData['reference'])
            || $this->hasShuftiProStructure($rawData);
    }

    public function getProviderName(): string
    {
        return 'shuftipro';
    }

    private function isValidValue($value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (is_string($value) && strtoupper(trim($value)) === 'N/A') {
            return false;
        }

        return true;
    }

    private function getFirstValid(...$values): ?string
    {
        foreach ($values as $value) {
            if ($this->isValidValue($value)) {
                return is_string($value) ? trim($value) : (string) $value;
            }
        }

        return null;
    }

    private function extractFirstName(array $rawData): ?string
    {
        $firstName = $this->getFirstValid(
            data_get($rawData, 'verification_data.document.first_name'),
            data_get($rawData, 'verification_data.document.name.first_name'),
            data_get($rawData, 'raw_response.verification_data.document.first_name'),
            data_get($rawData, 'raw_response.verification_data.document.name.first_name'),
            data_get($rawData, 'first_name'),
            data_get($rawData, 'firstName')
        );

        if ($firstName) {
            return $firstName;
        }

        $fullName = $this->getFirstValid(
            data_get($rawData, 'verification_data.document.name.full_name'),
            data_get($rawData, 'verification_data.document.full_name'),
            data_get($rawData, 'raw_response.verification_data.document.name.full_name'),
            data_get($rawData, 'raw_response.verification_data.document.full_name')
        );

        if ($fullName) {
            $nameParts = explode(' ', trim($fullName));

            return $nameParts[0] ?? null;
        }

        return null;
    }

    private function extractMiddleName(array $rawData): ?string
    {
        return $this->getFirstValid(
            data_get($rawData, 'verification_data.document.middle_name'),
            data_get($rawData, 'verification_data.document.name.middle_name'),
            data_get($rawData, 'middle_name'),
            data_get($rawData, 'middleName')
        );
    }

    private function extractLastName(array $rawData): ?string
    {
        $lastName = $this->getFirstValid(
            data_get($rawData, 'verification_data.document.last_name'),
            data_get($rawData, 'verification_data.document.name.last_name'),
            data_get($rawData, 'raw_response.verification_data.document.last_name'),
            data_get($rawData, 'raw_response.verification_data.document.name.last_name'),
            data_get($rawData, 'last_name'),
            data_get($rawData, 'lastName')
        );

        if ($lastName) {
            return $lastName;
        }

        $fullName = $this->getFirstValid(
            data_get($rawData, 'verification_data.document.name.full_name'),
            data_get($rawData, 'verification_data.document.full_name'),
            data_get($rawData, 'raw_response.verification_data.document.name.full_name'),
            data_get($rawData, 'raw_response.verification_data.document.full_name')
        );

        if ($fullName) {
            $nameParts = explode(' ', trim($fullName));
            if (count($nameParts) > 1) {
                array_shift($nameParts);

                return implode(' ', $nameParts);
            }
        }

        return null;
    }

    private function extractDateOfBirth(array $rawData): ?string
    {
        return $this->getFirstValid(
            data_get($rawData, 'verification_data.document.dob'),
            data_get($rawData, 'verification_data.document.date_of_birth'),
            data_get($rawData, 'raw_response.verification_data.document.dob'),
            data_get($rawData, 'raw_response.verification_data.document.date_of_birth'),
            data_get($rawData, 'dob'),
            data_get($rawData, 'date_of_birth'),
            data_get($rawData, 'dateOfBirth')
        );
    }

    private function extractGender(array $rawData): ?string
    {
        return $this->getFirstValid(
            data_get($rawData, 'verification_data.document.gender'),
            data_get($rawData, 'raw_response.verification_data.document.gender'),
            data_get($rawData, 'gender')
        );
    }

    private function extractNationality(array $rawData): ?string
    {
        return $this->getFirstValid(
            data_get($rawData, 'verification_data.document.nationality'),
            data_get($rawData, 'raw_response.verification_data.document.nationality'),
            data_get($rawData, 'nationality')
        );
    }

    private function extractCountry(array $rawData): ?string
    {
        return $this->getFirstValid(
            data_get($rawData, 'verification_data.document.country'),
            data_get($rawData, 'verification_data.document.issue_country'),
            data_get($rawData, 'verification_data.address.country'),
            data_get($rawData, 'raw_response.verification_data.document.country'),
            data_get($rawData, 'raw_response.verification_data.document.issue_country'),
            data_get($rawData, 'raw_response.verification_data.address.country'),
            data_get($rawData, 'country')
        );
    }

    private function extractPlaceOfBirth(array $rawData): ?string
    {
        return $this->getFirstValid(
            data_get($rawData, 'verification_data.document.place_of_birth'),
            data_get($rawData, 'additional_data.document.proof.place_of_birth'),
            data_get($rawData, 'raw_response.verification_data.document.place_of_birth'),
            data_get($rawData, 'raw_response.additional_data.document.proof.place_of_birth'),
            data_get($rawData, 'place_of_birth'),
            data_get($rawData, 'placeOfBirth')
        );
    }

    private function extractAddress(array $rawData): ?string
    {
        $address = $this->getFirstValid(
            data_get($rawData, 'address'),
            data_get($rawData, 'verification_data.address.full_address'),
            data_get($rawData, 'verification_data.document.full_address'),
            data_get($rawData, 'raw_response.verification_data.address.full_address'),
            data_get($rawData, 'raw_response.verification_data.document.full_address'),
            data_get($rawData, 'raw_response.additional_data.document.proof.address')
        );

        if ($address) {
            return $address;
        }

        $verificationAddress = data_get($rawData, 'verification_data.address');
        if ($verificationAddress && is_array($verificationAddress)) {
            $formattedAddress = $this->formatAddress($verificationAddress);
            if ($formattedAddress) {
                return $formattedAddress;
            }
        }

        $rawResponseAddress = data_get($rawData, 'raw_response.verification_data.address');
        if ($rawResponseAddress && is_array($rawResponseAddress)) {
            return $this->formatAddress($rawResponseAddress);
        }

        return null;
    }

    private function extractCity(array $rawData): ?string
    {
        return $this->getFirstValid(
            data_get($rawData, 'verification_data.address.city'),
            data_get($rawData, 'raw_response.verification_data.address.city'),
            data_get($rawData, 'raw_response.verification_data.address.address_decomposition.locality'),
            data_get($rawData, 'city')
        );
    }

    private function extractState(array $rawData): ?string
    {
        return $this->getFirstValid(
            data_get($rawData, 'verification_data.address.state'),
            data_get($rawData, 'verification_data.address.region'),
            data_get($rawData, 'raw_response.verification_data.address.state'),
            data_get($rawData, 'raw_response.verification_data.address.region'),
            data_get($rawData, 'raw_response.verification_data.address.address_decomposition.state'),
            data_get($rawData, 'raw_response.verification_data.address.address_decomposition.region'),
            data_get($rawData, 'state'),
            data_get($rawData, 'region')
        );
    }

    private function extractPostalCode(array $rawData): ?string
    {
        return $this->getFirstValid(
            data_get($rawData, 'verification_data.address.postal_code'),
            data_get($rawData, 'verification_data.address.zip_code'),
            data_get($rawData, 'postal_code'),
            data_get($rawData, 'zip_code')
        );
    }

    private function extractPhoneNumber(array $rawData): ?string
    {
        return $this->getFirstValid(
            data_get($rawData, 'raw_response.verification_data.phone.phone_number'),
            data_get($rawData, 'verification_data.phone.phone_number'),
            data_get($rawData, 'verification_data.phone'),
            data_get($rawData, 'phone_number'),
            data_get($rawData, 'phone')
        );
    }

    private function extractEmail(array $rawData): ?string
    {
        return $this->getFirstValid(data_get($rawData, 'email'));
    }

    private function extractDocuments(array $rawData): ?array
    {
        $document = data_get($rawData, 'verification_data.document');
        if ($document) {
            return [
                'document_type' => data_get($document, 'type'),
                'document_number' => data_get($document, 'number'),
                'issue_date' => data_get($document, 'issue_date'),
                'expiry_date' => data_get($document, 'expiry_date'),
            ];
        }

        return data_get($rawData, 'documents');
    }

    private function extractAdditionalData(array $rawData): ?array
    {
        $additionalData = [];

        $event = data_get($rawData, 'event');
        if ($event) {
            $additionalData['event'] = $event;
        }

        $reference = data_get($rawData, 'reference');
        if ($reference) {
            $additionalData['reference'] = $reference;
        }

        $result = data_get($rawData, 'result');
        if ($result) {
            $additionalData['result'] = $result;
        }

        $faceData = data_get($rawData, 'verification_data.face');
        if ($faceData) {
            $additionalData['face_verification'] = $faceData;
        }

        return ! empty($additionalData) ? $additionalData : null;
    }

    private function formatAddress(array $addressData): ?string
    {
        $addressParts = [];

        $fields = [
            'street', 'street_address', 'address_line_1',
            'city', 'state', 'region',
            'postal_code', 'zip_code', 'country',
        ];

        foreach ($fields as $field) {
            if (isset($addressData[$field]) && $this->isValidValue($addressData[$field])) {
                $addressParts[] = trim($addressData[$field]);
            }
        }

        return ! empty($addressParts) ? implode(', ', $addressParts) : null;
    }

    private function hasShuftiProStructure(array $rawData): bool
    {
        $shuftiProIndicators = [
            'verification_data',
            'declined_reason',
            'verification_result',
            'journey_id',
        ];

        foreach ($shuftiProIndicators as $indicator) {
            if (isset($rawData[$indicator])) {
                return true;
            }
        }

        return false;
    }
}
