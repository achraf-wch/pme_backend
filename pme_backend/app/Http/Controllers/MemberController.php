<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Http\Controllers\Concerns\ScopesByPartyBranch;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    use ScopesByPartyBranch;

    public function index()
    {
        $query = User::with(['role', 'partyBranch'])->latest();
        $branchIds = $this->userBranchIdsVisibleTo(request()->user());
        if ($branchIds !== null) {
            $query->whereIn('party_branch_id', $branchIds);
        }

        return response()->json($query->get());
    }

    public function show($id)
    {
        $query = User::with(['role', 'partyBranch'])->whereKey($id);
        $branchIds = $this->userBranchIdsVisibleTo(request()->user());
        if ($branchIds !== null) {
            $query->whereIn('party_branch_id', $branchIds);
        }

        return response()->json($query->firstOrFail());
    }

    public function update(Request $request, $id)
    {
        $query = User::query()->whereKey($id);
        $this->applyBranchScope($query, $request->user());
        $user = $query->firstOrFail();

        $data = $request->validate([
            'name'    => 'sometimes|string|max:255',
            'email'   => 'sometimes|email|unique:users,email,' . $id,
            'role_id' => 'sometimes|exists:roles,id',
            'role' => 'sometimes|string|exists:roles,name',
            'party_branch_id' => 'nullable|exists:party_branches,id',
        ]);

        if (isset($data['role'])) {
            $data['role_id'] = Role::where('name', $data['role'])->value('id');
            unset($data['role']);
        }

        if (array_key_exists('party_branch_id', $data)) {
            $data['party_branch_id'] = $this->branchIdForWrite($request->user(), $data['party_branch_id']);
        }

        $user->update($data);
        return response()->json($user->load(['role', 'partyBranch']));
    }

    public function destroy($id)
    {
        $query = User::query()->whereKey($id);
        $this->applyBranchScope($query, request()->user());
        $query->firstOrFail()->delete();
        return response()->json(['message' => 'Member deleted']);
    }
}
