<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SendGridWebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// الرابط سيكون: your-domain.com/api/webhooks/sendgrid
Route::post('/webhooks/sendgrid', [SendGridWebhookController::class, 'handle']);