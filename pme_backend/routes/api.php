<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MembershipRequestController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PollController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\NewsController;

use App\Http\Controllers\EventController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\SympathizerController;
use App\Http\Controllers\VolunteerController;


// ─────────────────────────────────────────
// PUBLIC ROUTES
// ─────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Public content
Route::get('/news',              [NewsController::class,  'index']);
Route::get('/news/{news}',       [NewsController::class,  'show']);
Route::get('/events',            [EventController::class, 'index']);
Route::get('/events/{id}',       [EventController::class, 'show']);
Route::get('/static-pages/{slug}', [StaticPageController::class, 'show']);

// Public forms
Route::post('/contact',              [ContactController::class,     'store']);
Route::post('/donations',            [DonationController::class,    'store']);
Route::post('/newsletter/subscribe', [NewsletterController::class,  'subscribe']);
Route::post('/sympathizer-request',  [SympathizerController::class, 'store']);
Route::post('/volunteer-request',    [VolunteerController::class,   'store']);

// ─────────────────────────────────────────
// PROTECTED ROUTES
// ─────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Membership request (any authenticated user)
    Route::post('/membership-request', [MembershipRequestController::class, 'store']);

    // Voting (controller handles audience check internally)
    Route::post('/vote', [PollController::class, 'vote']);

    // ─────────────────────────────────────────
    // MEMBER or ADMIN
    // ─────────────────────────────────────────
    Route::middleware('role:member,admin,sympathizer')->group(function () {

        // Profile
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);

        // Member data
        Route::get('/my-donations',          [DonationController::class, 'myDonations']);
        Route::get('/my-events',             [EventController::class,    'myRegistrations']);
        Route::post('/events/{id}/register', [EventController::class,    'register']);

        // Active polls
        Route::get('/polls/active', [PollController::class, 'active']);
    });

    // ─────────────────────────────────────────
    // ADMIN ONLY
    // ─────────────────────────────────────────
    Route::middleware('role:admin')->group(function () {

        // ── Membership requests ──
        Route::get('/admin/membership-requests',              [MembershipRequestController::class, 'indexPending']);
        Route::put('/admin/membership-requests/{id}/approve', [MembershipRequestController::class, 'approve']);
        Route::put('/admin/membership-requests/{id}/reject',  [MembershipRequestController::class, 'reject']);

        // ── Members ──
        Route::get('/admin/members',         [MemberController::class, 'index']);
        Route::get('/admin/members/{id}',    [MemberController::class, 'show']);
        Route::put('/admin/members/{id}',    [MemberController::class, 'update']);
        Route::delete('/admin/members/{id}', [MemberController::class, 'destroy']);

        // ── Sympathizers ──
        Route::get('/admin/sympathizers',         [SympathizerController::class, 'index']);
        Route::delete('/admin/sympathizers/{id}', [SympathizerController::class, 'destroy']);

        // ── Volunteers ──
        Route::get('/admin/volunteers',         [VolunteerController::class, 'index']);
        Route::delete('/admin/volunteers/{id}', [VolunteerController::class, 'destroy']);

        // ── Polls ──
        Route::get('/polls',              [PollController::class, 'index']);
        Route::post('/polls',             [PollController::class, 'store']);
        Route::put('/polls/{id}',         [PollController::class, 'update']);
        Route::delete('/polls/{id}',      [PollController::class, 'destroy']);
        Route::get('/polls/{id}/results', [PollController::class, 'results']);

        // ── Donations ──
        Route::get('/donations',               [DonationController::class, 'index']);
        Route::put('/donations/{donation}',    [DonationController::class, 'update']);
        Route::delete('/donations/{donation}', [DonationController::class, 'destroy']);

        // ── News ──
        Route::post('/news',          [NewsController::class, 'store']);
        Route::put('/news/{news}',    [NewsController::class, 'update']);
        Route::delete('/news/{news}', [NewsController::class, 'destroy']);

        // ── Contacts ──
        Route::get('/contacts',         [ContactController::class, 'index']);
        Route::delete('/contacts/{id}', [ContactController::class, 'destroy']);

        // ── Events ──
        Route::post('/events',                   [EventController::class, 'store']);
        Route::put('/events/{id}',               [EventController::class, 'update']);
        Route::delete('/events/{id}',            [EventController::class, 'destroy']);
        Route::get('/events/{id}/registrations', [EventController::class, 'registrations']);

        // ── Static pages ──
        Route::get('/static-pages',        [StaticPageController::class, 'index']);
        Route::put('/static-pages/{slug}', [StaticPageController::class, 'update']);

        // ── Media ──
        Route::get('/media',            [MediaController::class, 'index']);
        Route::post('/media',           [MediaController::class, 'store']);
        Route::delete('/media/{media}', [MediaController::class, 'destroy']);

        // ── Newsletter ──
        Route::get('/admin/newsletter',          [NewsletterController::class, 'index']);
        Route::delete('/admin/newsletter/{id}',  [NewsletterController::class, 'destroy']);
        Route::post('/admin/newsletter/send',    [NewsletterController::class, 'send']);

        // ── Stats ──
        Route::get('/admin/stats', [StatsController::class, 'index']);
    });
});