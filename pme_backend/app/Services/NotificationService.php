<?php

namespace App\Services;

use App\Models\PartyBranch;
use App\Models\User;
use App\Notifications\DashboardNotification;
use Illuminate\Support\Collection;

class NotificationService
{
    private const ADMIN_ROLES = ['central_admin', 'super_admin'];

    public function notifyAudience(array $audience, array $payload, ?int $exceptUserId = null, ?int $partyBranchId = null): void
    {
        $query = User::query()
            ->where('is_active', true)
            ->with(['role', 'partyBranch']);

        if (in_array('public', $audience, true)) {
            $query->whereHas('role');
        } else {
            $query->whereHas('role', fn ($roleQuery) => $roleQuery->whereIn('name', $this->roleNamesForAudience($audience)));
        }

        $branchIds = $this->recipientBranchIds($partyBranchId);
        if ($branchIds !== null) {
            $query->whereIn('party_branch_id', $branchIds);
        }

        if ($exceptUserId) {
            $query->whereKeyNot($exceptUserId);
        }

        $this->send($query->get(), $payload);
    }

    private function roleNamesForAudience(array $audience): array
    {
        $roles = $audience;

        if (in_array('member', $audience, true)) {
            $roles = array_merge($roles, ['local_official', 'regional_official', 'central_admin', 'super_admin']);
        }

        return array_values(array_unique($roles));
    }

    private function recipientBranchIds(?int $partyBranchId): ?array
    {
        if (!$partyBranchId) {
            return null;
        }

        $branch = PartyBranch::find($partyBranchId);
        if (!$branch || $branch->type === 'national') {
            return null;
        }

        if ($branch->type === 'regional') {
            $childIds = PartyBranch::where('parent_id', $branch->id)->pluck('id')->all();
            return array_map('intval', array_values(array_unique([$branch->id, ...$childIds])));
        }

        return [(int) $branch->id];
    }

    public function notifyAdmins(array $payload, ?int $exceptUserId = null): void
    {
        $query = User::query()
            ->where('is_active', true)
            ->whereHas('role', fn ($roleQuery) => $roleQuery->whereIn('name', self::ADMIN_ROLES));

        if ($exceptUserId) {
            $query->whereKeyNot($exceptUserId);
        }

        $this->send($query->get(), $payload);
    }

    private function send(Collection $users, array $payload): void
    {
        $payload = array_merge([
            'category' => 'system',
            'title' => 'Nouvelle notification',
            'body' => null,
            'action_url' => '/dashboard',
            'action_label' => 'Ouvrir',
            'source_type' => null,
            'source_id' => null,
        ], $payload);

        $users->unique('id')->each(fn (User $user) => $user->notify(new DashboardNotification($payload)));
    }
}
