<?php

use App\Http\Controllers\Api\Agent\AuthController;
use App\Http\Controllers\Api\Agent\CareerController;
use App\Http\Controllers\Api\Agent\CommissionController;
use App\Http\Controllers\Api\Agent\DashboardController;
use App\Http\Controllers\Api\Agent\DownlineController;
use App\Http\Controllers\Api\Agent\LeadController;
use App\Http\Controllers\Api\Agent\LookupsController;
use App\Http\Controllers\Api\Agent\NotificationController;
use App\Http\Controllers\Api\Agent\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Field Agent Mobile API (pristine-agro-app)
|--------------------------------------------------------------------------
| Base Prefix: /api/v1/agent
| Bearer Token Authentication via Laravel Sanctum
*/
Route::prefix('v1/agent')->group(function () {
    // Guest Auth
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Authenticated Agent Portal Endpoints
    Route::middleware('auth:sanctum')->group(function () {
        // Session
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Lookups Bootstrap
        Route::get('/lookups/bootstrap', [LookupsController::class, 'bootstrap']);

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Leads Management
        Route::get('/leads', [LeadController::class, 'index']);
        Route::post('/leads', [LeadController::class, 'store']);
        Route::get('/leads/{id}', [LeadController::class, 'show']);
        Route::match(['put', 'patch'], '/leads/{id}', [LeadController::class, 'update']);
        Route::patch('/leads/{id}/quick-status', [LeadController::class, 'quickStatus']);
        Route::delete('/leads/{id}', [LeadController::class, 'destroy']);

        // Downline Team Management
        Route::get('/downlines', [DownlineController::class, 'index']);
        Route::get('/downlines/next-id', [DownlineController::class, 'nextId']);
        Route::post('/downlines', [DownlineController::class, 'recruit']);
        Route::get('/downlines/{id}', [DownlineController::class, 'show']);

        // Commission Ledger
        Route::get('/commissions/overview', [CommissionController::class, 'overview']);
        Route::get('/commissions', [CommissionController::class, 'index']);
        Route::get('/commissions/{id}', [CommissionController::class, 'show']);

        // Career Progression & Targets
        Route::get('/career/evaluation', [CareerController::class, 'evaluation']);
        Route::get('/career/history', [CareerController::class, 'history']);
        Route::post('/career/promotion-requests', [CareerController::class, 'storePromotionRequest']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::match(['put', 'patch', 'post'], '/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::match(['put', 'patch'], '/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

        // Profile & Security
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::match(['put', 'patch'], '/profile', [ProfileController::class, 'update']);
        Route::post('/profile/password', [ProfileController::class, 'changePassword']);
    });
});
