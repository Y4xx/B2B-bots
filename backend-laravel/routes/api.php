<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\WebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public chat endpoint (authenticated via API key)
Route::post('/chat', [ChatController::class, 'chat']);

// Webhook routes (no authentication, verified by signature/token)
Route::post('/webhooks/scraper', [WebhookController::class, 'scraperCallback']);
Route::post('/webhooks/stripe', [WebhookController::class, 'stripeWebhook']);

// Protected routes (require Sanctum authentication)
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Routes that require active tenant
    Route::middleware(['tenant.active'])->group(function () {
        // Sites
        Route::apiResource('sites', SiteController::class);
        Route::post('/sites/{id}/reindex', [SiteController::class, 'reindex']);

        // Chat management (admin)
        Route::get('/sites/{siteId}/conversations', [ChatController::class, 'conversations']);
        Route::get('/conversations/{conversationId}', [ChatController::class, 'messages']);

        // Billing
        Route::get('/billing/subscription', [BillingController::class, 'getCurrentSubscription']);
        Route::post('/billing/checkout', [BillingController::class, 'createCheckoutSession']);
        Route::post('/billing/portal', [BillingController::class, 'createPortalSession']);
    });
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});
