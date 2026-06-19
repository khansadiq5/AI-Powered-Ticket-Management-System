<?php

use App\Http\Controllers\PostmarkWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the application and are assigned the "api"
| middleware group (no CSRF, stateless). They are prefixed with /api.
|
*/

// Postmark Inbound Email Webhook
// Full URL: POST /api/webhooks/inbound-email/{token}
// The {token} segment acts as a shared secret to prevent unauthorized access.
Route::post('/webhooks/inbound-email/{token}', [PostmarkWebhookController::class, 'handleInboundEmail'])
    ->name('webhooks.inbound-email');
