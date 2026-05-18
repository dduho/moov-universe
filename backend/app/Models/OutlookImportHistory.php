<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutlookImportHistory extends Model
{
    use HasFactory;

    protected $table = 'outlook_import_history';

    protected $fillable = [
        'mailbox',
        'message_id',
        'filename',
        'subject',
        'file_size_bytes',
        'file_hash',
        'received_at',
        'status',
        'transactions_imported',
        'transactions_updated',
        'transactions_skipped',
        'error_message',
        'processed_at',
        'retry_count',
        'last_retry_at',
        'metadata',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'last_retry_at' => 'datetime',
        'metadata' => 'json',
    ];

    /**
     * Scope: Get by message ID (for idempotence check)
     */
    public function scopeByMessageId($query, $messageId)
    {
        return $query->where('message_id', $messageId)->first();
    }

    /**
     * Scope: Get recent imports
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Get failed imports needing retry
     */
    public function scopeRetryable($query)
    {
        return $query->where('status', 'failed')
                     ->where('retry_count', '<', 3)
                     ->where('last_retry_at', '<', now()->subHours(1))
                     ->orWhereNull('last_retry_at');
    }

    /**
     * Mark as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'pending',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark as success with results
     */
    public function markAsSuccess($imported, $updated, $skipped): void
    {
        $this->update([
            'status' => 'success',
            'transactions_imported' => $imported,
            'transactions_updated' => $updated,
            'transactions_skipped' => $skipped,
            'processed_at' => now(),
            'retry_count' => 0,
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed($errorMessage, $retry = false): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'processed_at' => now(),
            'retry_count' => $this->retry_count + 1,
            'last_retry_at' => $retry ? now() : $this->last_retry_at,
        ]);
    }

    /**
     * Mark as partial (some rows imported, some failed)
     */
    public function markAsPartial($imported, $updated, $skipped, $errorMessage = null): void
    {
        $this->update([
            'status' => 'partial',
            'transactions_imported' => $imported,
            'transactions_updated' => $updated,
            'transactions_skipped' => $skipped,
            'error_message' => $errorMessage,
            'processed_at' => now(),
        ]);
    }
}
