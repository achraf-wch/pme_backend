<?php
namespace App\Http\Controllers;
use App\Models\Media;
use App\Http\Controllers\Concerns\ScopesByPartyBranch;
use App\Services\NotificationService;
use Illuminate\Http\Request;
class MediaController extends Controller {
    use ScopesByPartyBranch;

    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request) {
        $user = $request->user('sanctum') ?: $request->user();
        $role = optional($user?->loadMissing('role')->role)->name;
        $adminRoles = ['local_official', 'regional_official', 'central_admin', 'super_admin'];

        $query = Media::with(['uploader', 'partyBranch'])->latest();

        if ($request->query('audience') === 'mine' || !in_array($role, $adminRoles, true)) {
            $query->visibleTo($role, $user);
        } elseif ($user) {
            $this->applyManagedBranchScope($query, $user);
        }

        return response()->json($query->get());
    }

    public function store(Request $request) {
        $data = $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx|max:10240',
            'audience' => 'required|array|min:1',
            'audience.*' => 'string|in:public,visitor,sympathizer,volunteer,member,local_official,regional_official,central_admin,super_admin',
            'party_branch_id' => 'nullable|exists:party_branches,id',
        ]);
        $this->ensureAudienceAllowedForWrite($request->user(), $data['audience']);
        $this->ensureCanManageBranch($request->user(), $data['party_branch_id'] ?? null);

        $path = $request->file('file')->store('media', 'public');
        $media = Media::create([
            'file_name' => $request->file('file')->getClientOriginalName(),
            'file_url' => asset('storage/'.$path),
            'file_type' => $request->file('file')->getMimeType(),
            'file_size' => $request->file('file')->getSize(),
            'uploaded_by' => auth()->id(),
            'party_branch_id' => $this->branchIdForWrite($request->user(), $data['party_branch_id'] ?? null),
            'audience' => $data['audience'],
        ]);
        $this->notifications->notifyAudience($media->audience ?? ['public'], [
            'category' => 'media',
            'title' => 'Nouveau média disponible',
            'body' => $media->file_name,
            'action_url' => '/media',
            'action_label' => 'Ouvrir',
            'source_type' => 'media',
            'source_id' => $media->id,
        ], auth()->id(), $media->party_branch_id);
        return response()->json($media, 201);
    }
    public function destroy($id) {
        $media = Media::findOrFail($id);
        $branchIds = $this->managedBranchIdsVisibleTo(request()->user());

        if ($branchIds !== null && !in_array((int) $media->party_branch_id, $branchIds, true)) {
            abort(403, 'You are not allowed to manage this media item.');
        }

        $media->delete();
        return response()->json(['message'=>'Deleted']);
    }
}
