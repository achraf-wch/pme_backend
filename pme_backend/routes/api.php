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
use App\Http\Controllers\ProfileController;

// ─────────────────────────────────────────
// PUBLIC ROUTES (no authentication needed)
// ─────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/news', [NewsController::class, 'index']);              // published news for public
Route::post('/contact', [ContactController::class, 'store']);       // contact form submission
Route::get('/events', [EventController::class, 'index']);           // public event listing

// ─────────────────────────────────────────
// PROTECTED ROUTES (auth:sanctum required)
// ─────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Basic auth & user info
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Membership request submission (any authenticated user, role check inside controller)
    Route::post('/membership-request', [MembershipRequestController::class, 'store']);

    // Voting – open to any authenticated user, the controller checks poll->target_audience
    Route::post('/vote', [PollController::class, 'vote']);

    // ─────────────────────────────────────────
    // MEMBER or ADMIN only
    // ─────────────────────────────────────────
    Route::middleware(['role:member,admin'])->group(function () {
        // Profile
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);

        // Member's own donations
        Route::get('/my-donations', [DonationController::class, 'myDonations']);

        // Member's event registrations
        Route::get('/my-events', [EventController::class, 'myRegistrations']);
        Route::post('/events/{id}/register', [EventController::class, 'register']);

        // Active polls (only those where user is in target_audience)
        Route::get('/polls/active', [PollController::class, 'active']);
    });

    // ─────────────────────────────────────────
    // ADMIN ONLY
    // ─────────────────────────────────────────
    Route::middleware(['role:admin'])->group(function () {
        // Membership requests management
        Route::get('/admin/membership-requests', [MembershipRequestController::class, 'indexPending']);
        Route::put('/admin/membership-requests/{id}/approve', [MembershipRequestController::class, 'approve']);
        Route::put('/admin/membership-requests/{id}/reject', [MembershipRequestController::class, 'reject']);

        // Polls (admin CRUD & results)
        Route::get('/polls', [PollController::class, 'index']);
        Route::post('/polls', [PollController::class, 'store']);
        Route::get('/polls/{id}/results', [PollController::class, 'results']);

        // Donations (admin list & status update)
        Route::get('/donations', [DonationController::class, 'index']);
        Route::put('/donations/{donation}', [DonationController::class, 'update']);

        // News (full CRUD – index is public, but show/store/update/destroy are admin)
        Route::post('/news', [NewsController::class, 'store']);
        Route::get('/news/{news}', [NewsController::class, 'show']);
        Route::put('/news/{news}', [NewsController::class, 'update']);
        Route::delete('/news/{news}', [NewsController::class, 'destroy']);

        // Contacts (view list – store is public)
        Route::get('/contacts', [ContactController::class, 'index']);

        // Events (full CRUD – index is public)
        Route::post('/events', [EventController::class, 'store']);
        Route::get('/events/{id}', [EventController::class, 'show']);
        Route::put('/events/{id}', [EventController::class, 'update']);
        Route::delete('/events/{id}', [EventController::class, 'destroy']);
        Route::get('/events/{id}/registrations', [EventController::class, 'registrations']);

        // Static pages (edit)
        Route::get('/static-pages', [StaticPageController::class, 'index']);
        Route::put('/static-pages/{slug}', [StaticPageController::class, 'update']);

        // Media library
        Route::get('/media', [MediaController::class, 'index']);
        Route::post('/media', [MediaController::class, 'store']);
        Route::delete('/media/{media}', [MediaController::class, 'destroy']);
    });
});