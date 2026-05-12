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

        if (in_array($role, ['central_admin', 'super_admin'], true)) {
            return null;
        }

        if (!$user->party_branch_id) {
            return [];
        }

        $nationalIds = PartyBranch::where('type', 'national')->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($role === 'regional_official') {
            $childIds = PartyBranch::where('parent_id', $user->party_branch_id)->pluck('id')->all();
            return array_map('intval', array_values(array_unique([$user->party_branch_id, ...$childIds, ...$nationalIds])));
        }

        $branch = $user->loadMissing('partyBranch')->partyBranch;

        if ($branch?->type === 'local' && $branch->parent_id) {
            return array_values(array_unique([(int) $user->party_branch_id, (int) $branch->parent_id, ...$nationalIds]));
        }

        return array_values(array_unique([(int) $user->party_branch_id, ...$nationalIds]));
    }

    protected function userBranchIdsVisibleTo(User $user): ?array
    {
        $role = $this->roleName($user);

        if (in_array($role, ['central_admin', 'super_admin'], true)) {
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

    protected function managedBranchIdsVisibleTo(User $user): ?array
    {
        $role = $this->roleName($user);

        if (in_array($role, ['central_admin', 'super_admin'], true)) {
            return null;
        }

        if (!$user->party_branch_id) {
            return [];
        }

        if ($role === 'regional_official') {
            $childIds = PartyBranch::where('parent_id', $user->party_branch_id)->pluck('id')->all();
            return array_map('intval', array_values(array_unique([(int) $user->party_branch_id, ...$childIds])));
        }

        return [(int) $user->party_branch_id];
    }

    protected function applyManagedBranchScope(Builder $query, User $user, string $column = 'party_branch_id'): Builder
    {
        $branchIds = $this->managedBranchIdsVisibleTo($user);

        if ($branchIds === null) {
            return $query;
        }

        return $query->whereIn($column, $branchIds);
    }

    protected function applyBranchScope(Builder $query, User $user, string $column = 'party_branch_id'): Builder
    {
        $branchIds = $this->branchIdsVisibleTo($user);

        if ($branchIds === null) {
            return $query;
        }

        return $query->where(function ($scoped) use ($column, $branchIds) {
            $scoped->whereNull($column)->orWhereIn($column, $branchIds);
        });
    }

    protected function branchIdForWrite(User $user, ?int $requestedBranchId = null): ?int
    {
        $role = $this->roleName($user);

        if (in_array($role, ['central_admin', 'super_admin'], true)) {
            return $requestedBranchId ? (int) $requestedBranchId : null;
        }

        return $user->party_branch_id;
    }

    protected function ensureAudienceAllowedForWrite(User $user, array $audience): void
    {
        $role = $this->roleName($user);

        $allowed = match ($role) {
            'local_official', 'regional_official' => ['member'],
            default => ['public', 'visitor', 'sympathizer', 'volunteer', 'member', 'local_official', 'regional_official', 'central_admin', 'super_admin'],
        };

        if (array_diff($audience, $allowed)) {
            abort(403, 'This audience is outside your administrative scope.');
        }
    }

    protected function ensureCanManageBranch(User $user, ?int $branchId): void
    {
        $role = $this->roleName($user);

        if (in_array($role, ['central_admin', 'super_admin'], true)) {
            return;
        }

        if (!$user->party_branch_id) {
            abort(403, 'Your account is not attached to a party branch.');
        }

        if ($branchId && (int) $branchId !== (int) $user->party_branch_id) {
            abort(403, 'You can only publish inside your assigned branch.');
        }
    }
}
