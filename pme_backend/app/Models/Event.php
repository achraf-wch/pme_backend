<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'description',
        'location',
        'start_time',
        'end_time',
        'max_attendees',
        'created_by',
        'party_branch_id',
        'attachment_path',
        'audience',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
        'audience'   => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function partyBranch()
    {
        return $this->belongsTo(PartyBranch::class);
    }

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function recaps()
    {
        return $this->hasMany(EventRecap::class)->latest();
    }

    /**
     * Scope: only events visible to a given role (or public).
     */
    public function scopeVisibleTo($query, ?string $role = null, ?User $user = null)
    {
        return $query->where(function ($q) use ($role, $user) {
            $q->where(function ($audience) use ($role) {
                $audience->whereNull('audience')->orWhereJsonContains('audience', 'public');
                foreach (self::audienceKeysForRole($role) as $audienceKey) {
                    $audience->orWhereJsonContains('audience', $audienceKey);
                }
            });

            if (!$user) {
                $nationalIds = PartyBranch::where('type', 'national')->pluck('id')->all();
                $q->where(function ($branch) use ($nationalIds) {
                    $branch->whereNull('party_branch_id');
                    if ($nationalIds) {
                        $branch->orWhereIn('party_branch_id', $nationalIds);
                    }
                });
                return;
            }

            $branchIds = self::visibleBranchIdsFor($user);
            $q->where(function ($branch) use ($branchIds) {
                $branch->whereNull('party_branch_id');
                if ($branchIds === null) {
                    $branch->orWhereNotNull('party_branch_id');
                } elseif ($branchIds) {
                    $branch->orWhereIn('party_branch_id', $branchIds);
                }
            });
        });
    }

    private static function visibleBranchIdsFor(User $user): ?array
    {
        $role = $user->loadMissing('role')->role?->name;

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

        $branch = $user->partyBranch;

        if (in_array($role, ['visitor', 'sympathizer', 'volunteer', 'member'], true)
            && $branch?->type === 'regional') {
            return array_values(array_unique([(int) $user->party_branch_id, ...$nationalIds]));
        }

        if ($branch?->type === 'local' && $branch->parent_id) {
            return array_values(array_unique([(int) $user->party_branch_id, (int) $branch->parent_id, ...$nationalIds]));
        }

        if ($branch?->type === 'regional') {
            $childIds = PartyBranch::where('parent_id', $user->party_branch_id)->pluck('id')->all();
            return array_map('intval', array_values(array_unique([$user->party_branch_id, ...$childIds, ...$nationalIds])));
        }

        return array_values(array_unique([(int) $user->party_branch_id, ...$nationalIds]));
    }

    private static function audienceKeysForRole(?string $role): array
    {
        if (!$role) {
            return [];
        }

        $keys = [$role];

        if (in_array($role, ['local_official', 'regional_official', 'central_admin', 'super_admin'], true)) {
            $keys[] = 'member';
        }

        return array_values(array_unique($keys));
    }
}
