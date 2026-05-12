<?php

namespace App\Http\Controllers;

use App\Models\MembershipRequest;
use App\Models\PartyBranch;
use App\Models\User;
use App\Models\Role;
use App\Http\Controllers\Concerns\RecordsAuditLogs;
use App\Services\NotificationService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MembershipRequestController extends Controller
{
    use RecordsAuditLogs;

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

        // A user should not keep resubmitting while a request is still being reviewed.
        // Rejected requests are historical and should not block a fresh application.
        $existing = MembershipRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->first();
        if ($existing) {
            return response()->json([
                'message' => 'You already have a membership request.',
                'request' => $existing->load(['centralReviewer', 'superReviewer', 'reviewer', 'regionalBranch', 'localBranch']),
            ], 409);
        }

        $request->validate([
            'motivation' => 'nullable|string',
            'country' => 'required|string|max:120',
            'regional_branch_id' => 'required|exists:party_branches,id',
            'local_branch_id' => 'required|exists:party_branches,id',
            'age' => 'required|integer|min:16|max:120',
            'sex' => 'required|string|in:female,male,other,prefer_not_to_say',
        ]);

        $regionalBranch = PartyBranch::whereKey($request->regional_branch_id)
            ->where('type', 'regional')
            ->firstOrFail();
        $localBranch = PartyBranch::whereKey($request->local_branch_id)
            ->where('type', 'local')
            ->where('parent_id', $regionalBranch->id)
            ->firstOrFail();

        $membershipRequest = MembershipRequest::create([
            'user_id' => $user->id,
            'motivation' => $request->motivation,
            'country' => $request->country,
            'regional_branch_id' => $regionalBranch->id,
            'local_branch_id' => $localBranch->id,
            'age' => $request->age,
            'sex' => $request->sex,
            'status' => 'pending',
            'review_stage' => 'pending',
        ]);

        $this->audit($request, 'membership_request.created', $membershipRequest, [
            'user_id' => $user->id,
            'regional_branch_id' => $regionalBranch->id,
            'local_branch_id' => $localBranch->id,
            'review_stage' => 'pending',
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
            'request' => $membershipRequest->load(['regionalBranch', 'localBranch'])
        ], 201);
    }

    public function mine(Request $request)
    {
        return response()->json(
            MembershipRequest::with(['centralReviewer', 'superReviewer', 'reviewer', 'regionalBranch', 'localBranch'])
                ->where('user_id', $request->user()->id)
                ->where('status', 'pending')
                ->latest()
                ->first()
        );
    }

    // 2. Admin: get all pending requests
    public function indexPending()
    {
        $this->authorizeAdmin();

        $pending = MembershipRequest::with(['user.partyBranch', 'centralReviewer', 'superReviewer', 'reviewer', 'regionalBranch', 'localBranch'])
                                    ->latest()
                                    ->get();

        return response()->json($pending);
    }

    // 3. Admin: approve a request
    public function approve(Request $request, $id)
    {
        $this->authorizeAdmin();
        $actor = $request->user()->loadMissing('role');
        $role = $actor->role?->name;

        $membershipRequest = DB::transaction(function () use ($id, $actor, $role) {
            $membershipRequest = MembershipRequest::whereKey($id)->lockForUpdate()->firstOrFail();

            if ($membershipRequest->status !== 'pending') {
                throw new HttpResponseException(response()->json(['message' => 'Request already processed.'], 400));
            }

            if (!in_array($role, ['central_admin', 'super_admin'], true)) {
                throw new HttpResponseException(response()->json(['message' => 'Unauthorized.'], 403));
            }

            $memberRole = Role::where('name', 'member')->firstOrFail();
            $user = User::whereKey($membershipRequest->user_id)->lockForUpdate()->firstOrFail();
            $user->role_id = $memberRole->id;
            $user->party_branch_id = $membershipRequest->local_branch_id;
            $user->save();

            $membershipRequest->status = 'approved';
            $membershipRequest->review_stage = 'completed';
            if ($role === 'central_admin') {
                $membershipRequest->central_reviewed_by = $actor->id;
                $membershipRequest->central_reviewed_at = now();
            } else {
                $membershipRequest->super_reviewed_by = $actor->id;
                $membershipRequest->super_reviewed_at = now();
            }
            $membershipRequest->reviewed_by = $actor->id;
            $membershipRequest->reviewed_at = now();
            $membershipRequest->save();

            return $membershipRequest;
        });

        $this->audit($request, 'membership_request.approved', $membershipRequest, [
            'actor_role' => $role,
            'status' => $membershipRequest->status,
            'review_stage' => $membershipRequest->review_stage,
        ]);

        return response()->json([
            'message' => 'Membership approved. User is now a member.',
            'request' => $membershipRequest->load(['centralReviewer', 'superReviewer', 'reviewer', 'regionalBranch', 'localBranch']),
        ]);
    }

    // 4. Admin: reject a request
    public function reject(Request $request, $id)
    {
        $this->authorizeAdmin();

        $membershipRequest = MembershipRequest::findOrFail($id);

        if ($membershipRequest->status !== 'pending') {
            return response()->json(['message' => 'Request already processed.'], 400);
        }

        $membershipRequest->status = 'rejected';
        $membershipRequest->review_stage = 'rejected';
        $membershipRequest->reviewed_by = $request->user()->id;
        $membershipRequest->reviewed_at = now();
        if ($request->user()->loadMissing('role')->role?->name === 'super_admin') {
            $membershipRequest->super_reviewed_by = $request->user()->id;
            $membershipRequest->super_reviewed_at = now();
        } else {
            $membershipRequest->central_reviewed_by = $request->user()->id;
            $membershipRequest->central_reviewed_at = now();
        }
        $membershipRequest->save();

        $this->audit($request, 'membership_request.rejected', $membershipRequest, [
            'actor_role' => $request->user()->role?->name,
            'status' => $membershipRequest->status,
        ]);

        return response()->json([
            'message' => 'Membership request rejected.',
            'request' => $membershipRequest->load(['centralReviewer', 'superReviewer', 'reviewer', 'regionalBranch', 'localBranch']),
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
