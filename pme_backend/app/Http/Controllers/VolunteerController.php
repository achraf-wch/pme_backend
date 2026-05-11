<?php
namespace App\Http\Controllers;

use App\Models\Volunteer;
use App\Models\Role;
use App\Http\Controllers\Concerns\RecordsAuditLogs;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class VolunteerController extends Controller
{
    use RecordsAuditLogs;

    public function __construct(private NotificationService $notifications)
    {
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email',
            'phone'      => 'nullable|string|max:30',
            'party_branch_id' => 'nullable|exists:party_branches,id',
            'city'       => 'nullable|string|max:100',
            'skills'     => 'nullable|string',
            'motivation' => 'nullable|string',
        ]);

        if ($request->user()) {
            $data['user_id'] = $request->user()->id;
            $data['party_branch_id'] = $data['party_branch_id'] ?? $request->user()->party_branch_id;
        }

        $existing = Volunteer::where('email', $data['email'])
            ->orWhere(fn ($query) => $request->user()
                ? $query->where('user_id', $request->user()->id)
                : $query->whereRaw('1 = 0'))
            ->first();
        if ($existing) {
            return response()->json([
                'message' => 'You already submitted a volunteer request.',
                'request' => $existing->load(['partyBranch', 'reviewer']),
            ], 409);
        }

        $data['status'] = 'pending';
        $volunteer = Volunteer::create($data);

        $this->audit($request, 'volunteer_request.created', $volunteer, [
            'status' => $volunteer->status,
            'party_branch_id' => $volunteer->party_branch_id,
        ]);

        $this->notifications->notifyAdmins([
            'category' => 'request',
            'title' => 'Nouvelle demande bénévole',
            'body' => "{$volunteer->name} souhaite participer comme bénévole.",
            'action_url' => '/admin/volunteers',
            'action_label' => 'Voir les demandes',
            'source_type' => 'volunteer',
            'source_id' => $volunteer->id,
        ]);
        return response()->json(['message' => 'Volunteer request submitted', 'request' => $volunteer], 201);
    }

    public function index()
    {
        return response()->json(Volunteer::with(['partyBranch', 'reviewer'])->latest()->get());
    }

    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,in_progress,approved,rejected,completed',
        ]);

        $volunteer = Volunteer::findOrFail($id);
        if ($volunteer->status === 'completed') {
            return response()->json(['message' => 'Request already completed.'], 400);
        }

        $volunteer->update([
            'status' => $data['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($data['status'] === 'approved' && $volunteer->user_id) {
            $roleId = Role::where('name', 'volunteer')->value('id');
            if ($roleId) {
                $volunteer->user()->update(['role_id' => $roleId]);
            }
        }

        $this->audit($request, 'volunteer_request.status_updated', $volunteer, [
            'status' => $volunteer->status,
            'party_branch_id' => $volunteer->party_branch_id,
        ]);

        return response()->json($volunteer->load(['partyBranch', 'reviewer']));
    }

    public function mine(Request $request)
    {
        return response()->json(
            Volunteer::with(['partyBranch', 'reviewer'])
                ->where(fn ($query) => $query
                    ->where('user_id', $request->user()->id)
                    ->orWhere('email', $request->user()->email))
                ->latest()
                ->first()
        );
    }

    public function destroy(Request $request, $id)
    {
        $volunteer = Volunteer::findOrFail($id);
        $this->audit($request, 'volunteer_request.deleted', $volunteer, ['email' => $volunteer->email]);
        $volunteer->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
