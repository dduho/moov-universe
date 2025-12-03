<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PointOfSaleController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GeographyController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\ActivityLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

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

    // System settings routes
    Route::prefix('settings')->group(function () {
        Route::get('/', [SystemSettingController::class, 'index']);
        Route::get('/{key}', [SystemSettingController::class, 'show']);
        
        // Admin only - update settings
        Route::middleware('App\Http\Middleware\CheckRole:admin')->group(function () {
            Route::put('/{key}', [SystemSettingController::class, 'update']);
        });
    });

    // Point of Sale routes
    Route::prefix('point-of-sales')->group(function () {
        Route::get('/', [PointOfSaleController::class, 'index']);
        Route::post('/', [PointOfSaleController::class, 'store']);
        Route::get('/{id}', [PointOfSaleController::class, 'show']);
        Route::put('/{id}', [PointOfSaleController::class, 'update']);
        Route::delete('/{id}', [PointOfSaleController::class, 'destroy']);
        Route::post('/check-proximity', [PointOfSaleController::class, 'checkProximity']);
        
        // Admin only routes
        Route::middleware('App\Http\Middleware\CheckRole:admin')->group(function () {
            Route::post('/{id}/validate', [PointOfSaleController::class, 'validatePdv']);
            Route::post('/{id}/reject', [PointOfSaleController::class, 'reject']);
        });
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
        
        // Admin only
        Route::middleware('App\Http\Middleware\CheckRole:admin')->group(function () {
            Route::get('/by-organization', [StatisticsController::class, 'byOrganization']);
        });
    });

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
});

