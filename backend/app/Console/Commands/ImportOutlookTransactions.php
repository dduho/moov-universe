<?php

namespace App\Console\Commands;

use App\Http\Controllers\TransactionImportController;
use App\Models\OutlookImportHistory;
use App\Services\OutlookGraphService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ImportOutlookTransactions extends Command
{
    protected $signature = 'transactions:import-outlook {--mailbox= : Email address to import from}';

    protected $description = 'Import transaction files from Outlook mailbox via Microsoft Graph API';

    private OutlookGraphService $graphService;
    private TransactionImportController $transactionImporter;

    public function handle(): int
    {
        try {
            $this->graphService = app(OutlookGraphService::class);
            $this->transactionImporter = app(TransactionImportController::class);

            $mailbox = $this->option('mailbox') ?? config('services.outlook.mailbox');
            $folderName = config('services.outlook.mail_folder', 'Inbox');
            $subjectFilter = config('services.outlook.subject_filter');
            $filenamePattern = config('services.outlook.filename_pattern');
            $allowedExtensions = config('services.outlook.allowed_extensions', 'xlsx,xls');
            $maxFileMb = config('services.outlook.max_file_mb', 500);

            $this->info("Starting Outlook transaction import from {$mailbox}");
            $this->info("Scanning folder: {$folderName}");
            $this->info("Subject filter: {$subjectFilter}");

            // Fetch messages
            $messages = $this->graphService->getMessages(
                $mailbox,
                $folderName,
                $subjectFilter,
                true // Only unread messages
            );

            if (empty($messages)) {
                $this->info('No new messages found');
                return Command::SUCCESS;
            }

            $this->info("Found " . count($messages) . " message(s) to process");

            $successCount = 0;
            $failureCount = 0;

            foreach ($messages as $message) {
                try {
                    if (!$this->processMessage($message, $mailbox, $filenamePattern, $allowedExtensions, $maxFileMb)) {
                        $failureCount++;
                        continue;
                    }

                    $successCount++;

                    // Mark as read if configured
                    if (config('services.outlook.mark_as_read', true)) {
                        $this->graphService->markAsRead($mailbox, $message['id']);
                    }

                    // Move to Processed folder if configured
                    $processedFolder = config('services.outlook.move_processed_to');
                    if ($processedFolder) {
                        $this->graphService->moveToFolder($mailbox, $message['id'], $processedFolder);
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to process message {$message['subject']}: " . $e->getMessage());
                    $failureCount++;

                    // Move to Failed folder if configured
                    $failedFolder = config('services.outlook.move_failed_to');
                    if ($failedFolder) {
                        try {
                            $this->graphService->moveToFolder($mailbox, $message['id'], $failedFolder);
                        } catch (\Exception $moveException) {
                            Log::warning('Failed to move message to failed folder', [
                                'message_id' => $message['id'],
                                'error' => $moveException->getMessage(),
                            ]);
                        }
                    }
                }
            }

            $this->info("Import completed: {$successCount} success, {$failureCount} failures");

            return $failureCount > 0 ? Command::FAILURE : Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Outlook import failed: ' . $e->getMessage());
            Log::error('ImportOutlookTransactions command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Process a single message with its attachments
     */
    private function processMessage(
        $message,
        $mailbox,
        $filenamePattern,
        $allowedExtensions,
        $maxFileMb
    ): bool {
        $messageId = $message['id'];
        $subject = $message['subject'];
        $receivedAt = $message['receivedDateTime'];

        $this->info("Processing message: {$subject}");

        // Check if message was already processed
        $history = OutlookImportHistory::byMessageId($messageId);
        if ($history && $history->status === 'success') {
            $this->info("  └─ Already processed, skipping");
            return true;
        }

        // Get attachments
        if (!$message['hasAttachments']) {
            $this->warn("  └─ No attachments found");
            return false;
        }

        $attachments = $this->graphService->getAttachments($mailbox, $messageId);

        if (empty($attachments)) {
            $this->warn("  └─ No attachments to download");
            return false;
        }

        $processedAny = false;

        foreach ($attachments as $attachment) {
            try {
                if (!$this->processAttachment(
                    $attachment,
                    $messageId,
                    $subject,
                    $receivedAt,
                    $mailbox,
                    $filenamePattern,
                    $allowedExtensions,
                    $maxFileMb
                )) {
                    continue;
                }

                $processedAny = true;
            } catch (\Exception $e) {
                $this->error("  └─ Error processing attachment {$attachment['name']}: " . $e->getMessage());
            }
        }

        return $processedAny;
    }

    /**
     * Process a single attachment (download + import)
     */
    private function processAttachment(
        $attachment,
        $messageId,
        $subject,
        $receivedAt,
        $mailbox,
        $filenamePattern,
        $allowedExtensions,
        $maxFileMb
    ): bool {
        $filename = $attachment['name'];
        $attachmentId = $attachment['id'];
        $size = $attachment['size'] ?? 0;
        $contentType = $attachment['contentType'] ?? 'application/octet-stream';

        // Validate extension
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, array_map('strtolower', explode(',', $allowedExtensions)))) {
            $this->warn("  └─ Skipped (invalid extension): {$filename}");
            return false;
        }

        // Validate size
        if ($size > ($maxFileMb * 1024 * 1024)) {
            $this->error("  └─ Skipped (file too large): {$filename} ({$size} bytes)");
            return false;
        }

        // Validate filename pattern if provided
        if ($filenamePattern && !$this->matchesPattern($filename, $filenamePattern)) {
            $this->warn("  └─ Skipped (filename doesn't match pattern): {$filename}");
            return false;
        }

        $this->info("  ├─ Downloading: {$filename}");

        try {
            // Download attachment from Microsoft Graph
            $attachmentData = $this->graphService->downloadAttachment($mailbox, $messageId, $attachmentId);

            // For file attachments, we need to get the content
            if ($attachmentData['@odata.type'] === '#microsoft.graph.fileAttachment') {
                // The content is base64 encoded in the contentBytes property
                $content = base64_decode($attachmentData['contentBytes']);
            } else {
                throw new \Exception("Unsupported attachment type: " . $attachmentData['@odata.type']);
            }

            // Create temporary file
            $tmpPath = tempnam(sys_get_temp_dir(), 'outlook_txn_');
            file_put_contents($tmpPath, $content);

            // Create UploadedFile instance
            $uploadedFile = new UploadedFile(
                $tmpPath,
                $filename,
                $contentType,
                filesize($tmpPath),
                true
            );

            // Import the file
            $result = $this->transactionImporter->importUploadedFile($uploadedFile);

            // Calculate file hash for deduplication
            $fileHash = hash_file('sha256', $tmpPath);

            // Log the import
            OutlookImportHistory::create([
                'mailbox' => $mailbox,
                'message_id' => $messageId,
                'filename' => $filename,
                'subject' => $subject,
                'file_size_bytes' => $size,
                'file_hash' => $fileHash,
                'received_at' => Carbon::parse($receivedAt),
                'status' => 'success',
                'transactions_imported' => $result['imported'] ?? 0,
                'transactions_updated' => $result['updated'] ?? 0,
                'transactions_skipped' => $result['skipped'] ?? 0,
                'processed_at' => now(),
            ]);

            $this->info(
                "  └─ ✓ Import succeeded: {$result['imported']} imported, " .
                "{$result['updated']} updated, {$result['skipped']} skipped (date: {$result['date']})"
            );

            // Cleanup
            @unlink($tmpPath);

            return true;
        } catch (\Exception $e) {
            // Log the failure
            OutlookImportHistory::create([
                'mailbox' => $mailbox,
                'message_id' => $messageId,
                'filename' => $filename,
                'subject' => $subject,
                'file_size_bytes' => $size,
                'received_at' => Carbon::parse($receivedAt),
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'processed_at' => now(),
            ]);

            $this->error("  └─ ✗ Import failed: " . $e->getMessage());

            return false;
        }
    }

    /**
     * Check if filename matches pattern (simple wildcard support)
     * Example: "All Agent Consolidated Report_*.xlsx" matches "All Agent Consolidated Report_20260518.xlsx"
     */
    private function matchesPattern($filename, $pattern): bool
    {
        // Convert simple wildcard pattern to regex
        $regex = '/^' . preg_quote($pattern, '/') . '$/i';
        $regex = str_replace('\\*', '.*', $regex);

        return preg_match($regex, $filename) === 1;
    }
}
