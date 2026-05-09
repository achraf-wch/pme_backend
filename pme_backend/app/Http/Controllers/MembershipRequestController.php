<?php

namespace App\Http\Controllers;

use App\Models\MembershipRequest;
use App\Models\User;
use App\Models\Role;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MembershipRequestController extends Controller
{
    public function __construct(private NotificationService $notifications)
    {
    }

    // 1. Visitor submits a request to become a member
    public function store(Request $request)
    {
        $user = $request->user();

        // Check if user is already a member
        if ($user->role->name === 'member') {
            return response()->json(['message' => 'You are already a member.'], 400);
        }

        // Check if user already has a pending request
        $existing = MembershipRequest::where('user_id', $user->id)
                                      ->where('status', 'pending')
                                      ->first();
        if ($existing) {
            return response()->json(['message' => 'You already have a pending request.'], 400);
        }

        $request->validate([
            'motivation' => 'nullable|string',
        ]);

        $membershipRequest = MembershipRequest::create([
            'user_id' => $user->id,
            'motivation' => $request->motivation,
            'status' => 'pending',
        ]);

        $this->notifications->notifyAdmins([
            'category' => 'membership',
            'title' => 'Nouvelle demande d’adhésion',
            'body' => "{$user->name} souhaite devenir membre.",
            'action_url' => '/admin/dashboard',
            'action_label' => 'Examiner la demande',
            'source_type' => 'membership_request',
            'source_id' => $membershipRequest->id,
        ], $user->id);

        return response()->json([
            'message' => 'Membership request submitted. An admin will review it.',
            'request' => $membershipRequest
        ], 201);
    }

    // 2. Admin: get all pending requests
    public function indexPending()
    {
        $this->authorizeAdmin();

        $pending = MembershipRequest::with('user')
                                    ->where('status', 'pending')
                                    ->get();

        return response()->json($pending);
    }

    // 3. Admin: approve a request
    public function approve($id)
    {
        $this->authorizeAdmin();

        $membershipRequest = MembershipRequest::findOrFail($id);

        if ($membershipRequest->status !== 'pending') {
            return response()->json(['message' => 'Request already processed.'], 400);
        }

        $memberRole = Role::where('name', 'member')->first();
        $user = $membershipRequest->user;
        $user->role_id = $memberRole->id;
        $user->save();

        $membershipRequest->status = 'approved';
        $membershipRequest->reviewed_by = Auth::id();
        $membershipRequest->reviewed_at = now();
        $membershipRequest->save();

        return response()->json([
            'message' => 'Membership approved. User is now a member.'
        ]);
    }

    // 4. Admin: reject a request
    public function reject($id)
    {
        $this->authorizeAdmin();

        $membershipRequest = MembershipRequest::findOrFail($id);

        if ($membershipRequest->status !== 'pending') {
            return response()->json(['message' => 'Request already processed.'], 400);
        }

        $membershipRequest->status = 'rejected';
        $membershipRequest->reviewed_by = Auth::id();
        $membershipRequest->reviewed_at = now();
        $membershipRequest->save();

        return response()->json([
            'message' => 'Membership request rejected.'
        ]);
    }

    // Helper to ensure only admin can call these methods
    private function authorizeAdmin()
    {
        $role = Auth::user()->loadMissing('role')->role?->name;

        if (!in_array($role, ['central_admin', 'super_admin'], true)) {
            abort(403, 'Unauthorized. Admin access required.');
        }
    }
}
