<?php
namespace App\Http\Controllers;

use App\Models\Sympathizer;
use App\Models\Role;
use App\Http\Controllers\Concerns\RecordsAuditLogs;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class SympathizerController extends Controller
{
    use RecordsAuditLogs;

    public function __construct(private NotificationService $notifications)
    {
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email',
            'phone'   => 'nullable|string|max:30',
            'party_branch_id' => 'nullable|exists:party_branches,id',
            'city'    => 'nullable|string|max:100',
            'message' => 'nullable|string',
        ]);

        if ($request->user()) {
            $data['user_id'] = $request->user()->id;
            $data['name'] = $data['name'] ?: $request->user()->name;
            $data['email'] = $data['email'] ?: $request->user()->email;
            $data['party_branch_id'] = $data['party_branch_id'] ?? $request->user()->party_branch_id;
        }

        $existing = Sympathizer::where('email', $data['email'])
            ->orWhere(fn ($query) => $request->user()
                ? $query->where('user_id', $request->user()->id)
                : $query->whereRaw('1 = 0'))
            ->first();
        if ($existing) {
            return response()->json([
                'message' => 'You already submitted a sympathizer request.',
                'request' => $existing->load(['partyBranch', 'reviewer']),
            ], 409);
        }

        $data['status'] = 'pending';
        $sympathizer = Sympathizer::create($data);

        $this->audit($request, 'sympathizer_request.created', $sympathizer, [
            'status' => $sympathizer->status,
            'party_branch_id' => $sympathizer->party_branch_id,
        ]);

        $this->notifications->notifyAdmins([
            'category' => 'request',
            'title' => 'Nouvelle demande sympathisant',
            'body' => "{$sympathizer->name} souhaite rejoindre les sympathisants.",
            'action_url' => '/admin/sympathizers',
            'action_label' => 'Voir les demandes',
            'source_type' => 'sympathizer',
            'source_id' => $sympathizer->id,
        ]);
        return response()->json(['message' => 'Request submitted', 'request' => $sympathizer], 201);
    }

    public function index()
    {
        return response()->json(Sympathizer::with(['partyBranch', 'reviewer'])->latest()->get());
    }

    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,in_progress,approved,rejected,completed',
        ]);

        $sympathizer = Sympathizer::findOrFail($id);
        if ($sympathizer->status === 'completed') {
            return response()->json(['message' => 'Request already completed.'], 400);
        }

        $sympathizer->update([
            'status' => $data['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($data['status'] === 'approved' && $sympathizer->user_id) {
            $roleId = Role::where('name', 'sympathizer')->value('id');
            if ($roleId) {
                $sympathizer->user()->update(['role_id' => $roleId]);
            }
        }

        $this->audit($request, 'sympathizer_request.status_updated', $sympathizer, [
            'status' => $sympathizer->status,
            'party_branch_id' => $sympathizer->party_branch_id,
        ]);

        return response()->json($sympathizer->load(['partyBranch', 'reviewer']));
    }

    public function mine(Request $request)
    {
        return response()->json(
            Sympathizer::with(['partyBranch', 'reviewer'])
                ->where(fn ($query) => $query
                    ->where('user_id', $request->user()->id)
                    ->orWhere('email', $request->user()->email))
                ->latest()
                ->first()
        );
    }

    public function destroy(Request $request, $id)
    {
        $sympathizer = Sympathizer::findOrFail($id);
        $this->audit($request, 'sympathizer_request.deleted', $sympathizer, ['email' => $sympathizer->email]);
        $sympathizer->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
