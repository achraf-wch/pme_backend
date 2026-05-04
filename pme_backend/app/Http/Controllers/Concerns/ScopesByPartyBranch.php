<?php

namespace App\Http\Controllers\Concerns;

use App\Models\PartyBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait ScopesByPartyBranch
{
    protected function roleName(User $user): ?string
    {
        return $user->loadMissing('role')->role?->name;
    }

    protected function branchIdsVisibleTo(User $user): ?array
    {
        $role = $this->roleName($user);

        if (in_array($role, ['central_admin', 'admin', 'super_admin'], true)) {
            return null;
        }

        if (!$user->party_branch_id) {
            return [];
        }

        if ($role === 'regional_official') {
            $childIds = PartyBranch::where('parent_id', $user->party_branch_id)->pluck('id')->all();
            return array_map('intval', array_values(array_unique([$user->party_branch_id, ...$childIds])));
        }

        if ($role === 'local_official') {
            return [(int) $user->party_branch_id];
        }

        return [(int) $user->party_branch_id];
    }

    protected function applyBranchScope(Builder $query, User $user, string $column = 'party_branch_id'): Builder
    {
        $branchIds = $this->branchIdsVisibleTo($user);

        if ($branchIds === null) {
            return $query;
        }

        return $query->whereIn($column, $branchIds);
    }

    protected function branchIdForWrite(User $user, ?int $requestedBranchId = null): ?int
    {
        $role = $this->roleName($user);

        if (in_array($role, ['central_admin', 'admin', 'super_admin'], true)) {
            return $requestedBranchId ? (int) $requestedBranchId : $user->party_branch_id;
        }

        return $user->party_branch_id;
    }
}
