<?php

namespace Asciisd\KycShuftiPro\Services;

use Asciisd\KycShuftiPro\Exceptions\ShuftiProException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ShuftiProDocumentService
{
    private string $storageDisk;

    private string $storagePath;

    private bool $loggingEnabled;

    private string $logChannel;

    public function __construct()
    {
        $this->storageDisk = config('kyc.settings.document_storage_disk', 's3');
        $this->storagePath = config('kyc.settings.document_storage_path', 'kyc/documents');
        $this->loggingEnabled = config('shuftipro.logging.enabled', true);
        $this->logChannel = config('shuftipro.logging.channel', 'daily');
    }

    /**
     * Download and store all documents for a user
     */
    public function downloadAndStoreDocuments(Model $user, string $reference): array
    {
        $this->logActivity('Starting document download', [
            'user_id' => $user->getKey(),
            'reference' => $reference,
        ]);

        $downloadedFiles = [];

        try {
            // Get document URLs from ShuftiPro API
            $documentUrls = $this->getDocumentUrls($reference);

            if (empty($documentUrls)) {
                $this->logActivity('No documents found for reference', ['reference' => $reference]);

                return $downloadedFiles;
            }

            foreach ($documentUrls as $documentType => $url) {
                if (is_array($url)) {
                    // Handle multiple documents of the same type
                    foreach ($url as $index => $singleUrl) {
                        $filePath = $this->downloadAndStoreDocument($user, $reference, $documentType, $singleUrl, $index);
                        if ($filePath) {
                            $downloadedFiles[] = $filePath;
                        }
                    }
                } else {
                    // Handle single document
                    $filePath = $this->downloadAndStoreDocument($user, $reference, $documentType, $url);
                    if ($filePath) {
                        $downloadedFiles[] = $filePath;
                    }
                }
            }

            $this->logActivity('Document download completed', [
                'user_id' => $user->getKey(),
                'reference' => $reference,
                'files_count' => count($downloadedFiles),
            ]);

        } catch (\Exception $e) {
            $this->logActivity('Document download failed', [
                'user_id' => $user->getKey(),
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            throw new ShuftiProException('Failed to download documents: '.$e->getMessage());
        }

        return $downloadedFiles;
    }

    /**
     * Download and store a single document
     */
    private function downloadAndStoreDocument(Model $user, string $reference, string $documentType, string $url, int $index = 0): ?string
    {
        try {
            // Download document content
            $response = Http::timeout(60)->get($url);

            if (! $response->successful()) {
                $this->logActivity('Failed to download document', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return null;
            }

            // Generate file path
            $fileName = $this->generateFileName($user, $reference, $documentType, $index);
            $filePath = $this->storagePath.'/'.$fileName;

            // Store document
            $stored = Storage::disk($this->storageDisk)->put($filePath, $response->body());

            if (! $stored) {
                $this->logActivity('Failed to store document', ['file_path' => $filePath]);

                return null;
            }

            $this->logActivity('Document stored successfully', [
                'file_path' => $filePath,
                'document_type' => $documentType,
            ]);

            return $filePath;

        } catch (\Exception $e) {
            $this->logActivity('Error downloading document', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get document URLs from ShuftiPro API
     */
    private function getDocumentUrls(string $reference): array
    {
        // This would typically call the ShuftiPro API to get document URLs
        // For now, we'll return a placeholder structure
        return [
            'document_front' => 'https://example.com/document_front.jpg',
            'document_back' => 'https://example.com/document_back.jpg',
            'selfie' => 'https://example.com/selfie.jpg',
        ];
    }

    /**
     * Generate unique file name
     */
    private function generateFileName(Model $user, string $reference, string $documentType, int $index = 0): string
    {
        $extension = $this->getFileExtension($documentType);
        $indexSuffix = $index > 0 ? '_'.$index : '';

        return sprintf(
            '%s_%s_%s%s.%s',
            $user->getKey(),
            $reference,
            $documentType,
            $indexSuffix,
            $extension
        );
    }

    /**
     * Get file extension based on document type
     */
    private function getFileExtension(string $documentType): string
    {
        return match ($documentType) {
            'document_front', 'document_back' => 'jpg',
            'selfie' => 'jpg',
            'verification_video' => 'mp4',
            'verification_report' => 'pdf',
            default => 'jpg',
        };
    }

    /**
     * Get stored document URL
     */
    public function getDocumentUrl(string $filePath): string
    {
        return Storage::disk($this->storageDisk)->url($filePath);
    }

    /**
     * Check if document exists
     */
    public function documentExists(string $filePath): bool
    {
        return Storage::disk($this->storageDisk)->exists($filePath);
    }

    /**
     * Delete document
     */
    public function deleteDocument(string $filePath): bool
    {
        if ($this->documentExists($filePath)) {
            return Storage::disk($this->storageDisk)->delete($filePath);
        }

        return true;
    }

    /**
     * Log activity if logging is enabled
     */
    private function logActivity(string $message, array $data = []): void
    {
        if ($this->loggingEnabled) {
            Log::channel($this->logChannel)->info($message, $data);
        }
    }
}
