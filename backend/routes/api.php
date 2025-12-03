<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PointOfSaleController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\GeographyController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\StatisticsController;
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
            Route::post('/{id}/validate', [PointOfSaleController::class, 'validate']);
            Route::post('/{id}/reject', [PointOfSaleController::class, 'reject']);
        });
    });

    // Organization routes (Admin only)
    Route::middleware('App\Http\Middleware\CheckRole:admin')->group(function () {
        Route::apiResource('organizations', OrganizationController::class);
    });

    // Statistics routes
    Route::prefix('statistics')->group(function () {
        Route::get('/dashboard', [StatisticsController::class, 'dashboard']);
        Route::get('/by-region', [StatisticsController::class, 'byRegion']);
        Route::get('/timeline', [StatisticsController::class, 'timeline']);
        
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
});

