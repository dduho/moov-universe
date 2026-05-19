<?php

namespace App\Http\Controllers;

use App\Models\OutlookImportHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class OutlookImportController extends Controller
{
    /**
     * Run Outlook import manually
     * POST /api/import/outlook/run
     */
    public function runManual(Request $request)
    {
        // Check admin role
        if (!auth('sanctum')->user() || auth('sanctum')->user()->role?->name !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized - Admin access required'
            ], 403);
        }

        try {
            // Execute the command in background
            Artisan::queue('transactions:import-outlook');

            Log::info('[OutlookImport] Manual import triggered by admin', [
                'user_id' => auth('sanctum')->user()->id,
                'timestamp' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Import Outlook lancé en arrière-plan. Vous recevrez une notification à la fin.',
                'timestamp' => now()
            ], 202);

        } catch (\Exception $e) {
            Log::error('[OutlookImport] Manual import failed', [
                'error' => $e->getMessage(),
                'user_id' => auth('sanctum')->user()->id
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du lancement de l\'import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get import history
     * GET /api/import/outlook/history?limit=10
     */
    public function getHistory(Request $request)
    {
        // Check admin role
        if (!auth('sanctum')->user() || auth('sanctum')->user()->role?->name !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized - Admin access required'
            ], 403);
        }

        try {
            $limit = $request->get('limit', 10);
            $status = $request->get('status'); // Filter by status if provided

            $query = OutlookImportHistory::orderBy('created_at', 'desc');

            if ($status) {
                $query->where('status', $status);
            }

            $history = $query->limit($limit)->get([
                'id',
                'mailbox',
                'message_id',
                'filename',
                'subject',
                'file_size_bytes',
                'received_at',
                'status',
                'transactions_imported',
                'transactions_updated',
                'transactions_skipped',
                'error_message',
                'retry_count',
                'last_retry_at',
                'processed_at',
                'created_at'
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $history,
                'count' => $history->count()
            ], 200);

        } catch (\Exception $e) {
            Log::error('[OutlookImport] History fetch failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération de l\'historique: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Outlook status
     * GET /api/import/outlook/status
     */
    public function getStatus(Request $request)
    {
        // Check admin role
        if (!auth('sanctum')->user() || auth('sanctum')->user()->role?->name !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized - Admin access required'
            ], 403);
        }

        try {
            $latestImport = OutlookImportHistory::where('status', 'success')
                ->orderBy('processed_at', 'desc')
                ->first();

            $todayCount = OutlookImportHistory::whereDate('created_at', today())->count();
            $todaySuccessCount = OutlookImportHistory::whereDate('created_at', today())
                ->where('status', 'success')
                ->count();
            $todayFailureCount = OutlookImportHistory::whereDate('created_at', today())
                ->where('status', 'failed')
                ->count();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'enabled' => config('services.outlook.import_enabled', false),
                    'last_successful_import' => $latestImport?->processed_at,
                    'today_total_imports' => $todayCount,
                    'today_successful' => $todaySuccessCount,
                    'today_failed' => $todayFailureCount,
                    'scheduled_time' => config('services.outlook.import_time', '08:30'),
                    'timezone' => config('services.outlook.import_timezone', 'UTC'),
                    'mailbox' => config('services.outlook.mailbox', 'Not configured'),
                    'mail_folder' => config('services.outlook.mail_folder', 'Inbox'),
                    'subject_filter' => config('services.outlook.subject_filter', 'N/A'),
                    'filename_pattern' => config('services.outlook.filename_pattern', '*.xlsx'),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('[OutlookImport] Status fetch failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération du statut: ' . $e->getMessage()
            ], 500);
        }
    }
}
