<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MembershipRequestController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PollController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MediaController;

// Public routes (no authentication needed)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('news', [NewsController::class, 'index']); // published only? adjust later
Route::post('contact', [ContactController::class, 'store']);
Route::get('events', [EventController::class, 'index']); // show upcoming
// For event registration (authenticated)
Route::middleware('auth:sanctum')->post('events/{id}/register', [EventController::class, 'register']);

// Protected routes (require valid token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Membership request submission (visitors)
    Route::post('/membership-request', [MembershipRequestController::class, 'store']);

    // Admin-only routes (checked inside controller)
  Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/membership-requests', [MembershipRequestController::class, 'indexPending']);
    Route::put('/admin/membership-requests/{id}/approve', [MembershipRequestController::class, 'approve']);
    Route::put('/admin/membership-requests/{id}/reject', [MembershipRequestController::class, 'reject']);
            Route::get('/polls', [PollController::class, 'index']);
        Route::post('/polls', [PollController::class, 'store']);
        Route::get('/polls/{id}/results', [PollController::class, 'results']);
        Route::apiResource('donations', DonationController::class)->only(['index', 'update']);
    // News (full CRUD)
    Route::apiResource('news', NewsController::class);
    // Contacts (only index, store is public)
    Route::get('contacts', [ContactController::class, 'index']);
    // Events
    Route::apiResource('events', EventController::class);
    Route::get('events/{id}/registrations', [EventController::class, 'registrations']);
    // Static pages
    Route::get('static-pages', [StaticPageController::class, 'index']);
    Route::put('static-pages/{slug}', [StaticPageController::class, 'update']);
    // Media
    Route::apiResource('media', MediaController::class)->only(['index', 'store', 'destroy']);
});
    Route::get('/polls/active', [PollController::class, 'active']);
    Route::post('/vote', [PollController::class, 'vote']);
});