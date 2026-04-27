<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MembershipRequestController;
use Illuminate\Support\Facades\Route;

// Public routes (no authentication needed)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (require valid token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Membership request submission (visitors)
    Route::post('/membership-request', [MembershipRequestController::class, 'store']);

    // Admin-only routes (checked inside controller)
    Route::get('/admin/membership-requests', [MembershipRequestController::class, 'indexPending']);
    Route::put('/admin/membership-requests/{id}/approve', [MembershipRequestController::class, 'approve']);
    Route::put('/admin/membership-requests/{id}/reject', [MembershipRequestController::class, 'reject']);
});