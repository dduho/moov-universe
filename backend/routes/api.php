<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PointOfSaleController;
use App\Http\Controllers\PointOfSaleImportController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GeographyController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\DealerStatsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MailTestController;
use App\Http\Controllers\TransactionImportController;
use App\Http\Controllers\PdvStatsController;
use App\Http\Controllers\TransactionAnalyticsController;
use App\Http\Controllers\AnalyticsInsightsController;
use App\Http\Controllers\ComparatorController;
use App\Http\Controllers\GlobalSearchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/password-rules', [AuthController::class, 'getPasswordRules']);

// Public system settings (read-only)
Route::prefix('settings')->group(function () {
    Route::get('/', [SystemSettingController::class, 'index']);
    Route::get('/{key}', [SystemSettingController::class, 'show']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

    // Geography routes (public for authenticated users)
    Route::prefix('geography')->group(function () {
        Route::get('/regions', [GeographyController::class, 'getRegions']);
        Route::get('/prefectures', [GeographyController::class, 'getPrefectures']);
        Route::get('/communes', [GeographyController::class, 'getCommunes']);
        Route::get('/cantons', [GeographyController::class, 'getCantons']);
        Route::get('/hierarchy', [GeographyController::class, 'getHierarchy']);
    });

    // Upload routes
    Route::post('/uploads', [UploadController::class, 'upload']);
    Route::post('/uploads/multiple', [UploadController::class, 'uploadMultiple']);
    Route::delete('/uploads', [UploadController::class, 'delete']);

    // System settings update (admin only)
    Route::middleware('App\Http\Middleware\CheckRole:admin')->group(function () {
        Route::put('/settings/{key}', [SystemSettingController::class, 'update']);
        Route::get('/mail/test-smtp', [MailTestController::class, 'testSmtpConnection']);
    });

    // Point of Sale routes
    Route::prefix('point-of-sales')->group(function () {
        Route::get('/', [PointOfSaleController::class, 'index']);
        Route::get('/for-map', [PointOfSaleController::class, 'forMap']);
        Route::get('/gps-stats', [PointOfSaleController::class, 'getGpsStats']);
        Route::get('/proximity-alerts', [PointOfSaleController::class, 'getProximityAlerts']);
        Route::post('/', [PointOfSaleController::class, 'store']);
        Route::get('/{id}', [PointOfSaleController::class, 'show']);
        Route::put('/{id}', [PointOfSaleController::class, 'update']);
        Route::delete('/{id}', [PointOfSaleController::class, 'destroy']);
        Route::post('/check-proximity', [PointOfSaleController::class, 'checkProximity']);
        Route::post('/check-uniqueness', [PointOfSaleController::class, 'checkUniqueness']);
        
        // Import routes (Admin only)
        Route::middleware('App\Http\Middleware\CheckRole:admin')->group(function () {
            Route::post('/import/preview', [PointOfSaleImportController::class, 'preview']);
            Route::post('/import', [PointOfSaleImportController::class, 'import']);
            Route::post('/import/export-analysis', [PointOfSaleImportController::class, 'exportAnalysis']);
            Route::get('/import/template', [PointOfSaleImportController::class, 'downloadTemplate']);
            Route::post('/clear-duplicate-coordinates', [PointOfSaleController::class, 'clearDuplicateCoordinates']);
        });
        
        // Admin only routes
        Route::middleware('App\Http\Middleware\CheckRole:admin')->group(function () {
            Route::post('/{id}/validate', [PointOfSaleController::class, 'validatePdv']);
            Route::post('/{id}/reject', [PointOfSaleController::class, 'reject']);
        });

        // Notes routes (accessible à tous les utilisateurs authentifiés)
        Route::get('/{pointOfSaleId}/notes', [NoteController::class, 'index']);
        Route::post('/{pointOfSaleId}/notes', [NoteController::class, 'store']);
        Route::put('/{pointOfSaleId}/notes/{noteId}', [NoteController::class, 'update']);
        Route::delete('/{pointOfSaleId}/notes/{noteId}', [NoteController::class, 'destroy']);
        Route::post('/{pointOfSaleId}/notes/{noteId}/toggle-pin', [NoteController::class, 'togglePin']);
    });

    // Organization routes (Admin only)
    Route::middleware('App\Http\Middleware\CheckRole:admin')->group(function () {
        Route::apiResource('organizations', OrganizationController::class);
    });

    // User routes (Admin only)
    Route::middleware('App\Http\Middleware\CheckRole:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
        Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);
        Route::post('/users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
    });

    // Statistics routes
    Route::prefix('statistics')->group(function () {
        Route::get('/dashboard', [StatisticsController::class, 'dashboard']);
        Route::get('/by-region', [StatisticsController::class, 'byRegion']);
        Route::get('/timeline', [StatisticsController::class, 'timeline']);
        Route::get('/validation', [StatisticsController::class, 'validation']);
        Route::get('/geo-alerts', [StatisticsController::class, 'geoAlerts']);
        
        // Admin only
        Route::middleware('App\Http\Middleware\CheckRole:admin')->group(function () {
            Route::get('/by-organization', [StatisticsController::class, 'byOrganization']);
        });
    });

    // Dealer stats
    Route::get('/dealers/{id}/stats', [DealerStatsController::class, 'stats']);

    // Export routes
    Route::prefix('export')->group(function () {
        Route::get('/xml', [ExportController::class, 'exportXml']);
        Route::get('/csv', [ExportController::class, 'exportCsv']);
    });

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        
        // Admin only - create notifications
        Route::middleware('App\Http\Middleware\CheckRole:admin')->group(function () {
            Route::post('/', [NotificationController::class, 'store']);
        });
    });

    // Activity Log routes (Admin only)
    Route::middleware('App\Http\Middleware\CheckRole:admin')->group(function () {
        Route::get('/activity-logs', [ActivityLogController::class, 'index']);
    });

    // Task routes
    Route::prefix('tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index']);
        Route::get('/{id}', [TaskController::class, 'show']);
        
        // Commercial can complete their tasks
        Route::post('/{id}/complete', [TaskController::class, 'complete']);
        
        // Admin only routes
        Route::middleware('App\\Http\\Middleware\\CheckRole:admin')->group(function () {
            Route::post('/', [TaskController::class, 'store']);
            Route::post('/{id}/validate', [TaskController::class, 'validateTask']);
            Route::post('/{id}/request-revision', [TaskController::class, 'requestRevision']);
            Route::delete('/{id}', [TaskController::class, 'destroy']);
            Route::get('/commercials/{pointOfSaleId}', [TaskController::class, 'getCommercialsByDealer']);
        });
    });

    // Transaction import routes (Admin only)
    Route::prefix('transactions')->middleware('App\\Http\\Middleware\\CheckRole:admin')->group(function () {
        Route::post('/import', [TransactionImportController::class, 'import']);
    });

    // Transaction Analytics routes (Admin only)
    Route::prefix('analytics')->middleware('App\\Http\\Middleware\\CheckRole:admin')->group(function () {
        Route::get('/transactions', [TransactionAnalyticsController::class, 'getAnalytics']);
        Route::get('/insights', [AnalyticsInsightsController::class, 'getInsights']);
    });

    // Comparator routes (Admin only)
    Route::prefix('comparator')->middleware('App\\Http\\Middleware\\CheckRole:admin')->group(function () {
        Route::post('/compare', [ComparatorController::class, 'compare']);
    });

    // Search endpoints for comparator (Admin only)
    Route::middleware('App\\Http\\Middleware\\CheckRole:admin')->group(function () {
        Route::get('/pdv', [ComparatorController::class, 'searchPdvs']);
        Route::get('/dealers', [ComparatorController::class, 'searchDealers']);
    });

    // PDV Stats routes
    Route::prefix('pdv')->group(function () {
        Route::get('/{id}/stats', [PdvStatsController::class, 'getStats']);
    });

    // Global Search routes (Available for all authenticated users)
    Route::prefix('search')->group(function () {
        Route::get('/', [GlobalSearchController::class, 'search']);
        Route::get('/suggestions', [GlobalSearchController::class, 'suggestions']);
    });
});
