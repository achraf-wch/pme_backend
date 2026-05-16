<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Poll extends Model
{
    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'is_secret',
        'created_by',
        'party_branch_id',
        'target_audience',
    ];

    protected $casts = [
        'start_date'      => 'datetime',
        'end_date'        => 'datetime',
        'is_secret'       => 'boolean',
        'target_audience' => 'array',
    ];

    public function options()
    {
        return $this->hasMany(PollOption::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function partyBranch()
    {
        return $this->belongsTo(PartyBranch::class);
    }

    public function userCanVote($user): bool
    {
        if (!$user || !$user->role) {
            return false;
        }

        $audience = $this->target_audience ?? [];

        if (!self::audienceAllowsRole($audience, $user->role->name)) {
            return false;
        }

        if (!$this->party_branch_id) {
            return true;
        }

        $branchIds = self::visibleBranchIdsFor($user);

        return $branchIds === null || in_array((int) $this->party_branch_id, $branchIds, true);
    }

    /**
     * Scope: active polls visible to a given role (or public).
     */
    public function scopeVisibleTo($query, ?string $role = null, ?User $user = null)
    {
        return $query->where(function ($q) use ($role, $user) {
            $q->where(function ($audience) use ($role) {
                $audience->whereNull('target_audience')->orWhereJsonContains('target_audience', 'public');
                foreach (self::audienceKeysForRole($role) as $audienceKey) {
                    $audience->orWhereJsonContains('target_audience', $audienceKey);
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

    /**
     * Scope: only currently active polls.
     */
    public function scopeActive($query)
    {
        $now = now();
        return $query->where('end_date', '>=', $now);
    }

    private static function audienceAllowsRole(array $audience, ?string $role): bool
    {
        if (in_array('public', $audience, true)) {
            return true;
        }

        if (!$role) {
            return false;
        }

        return (bool) array_intersect($audience, self::audienceKeysForRole($role));
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

        if ($branch?->type === 'local' && $branch->parent_id) {
            return array_values(array_unique([(int) $user->party_branch_id, (int) $branch->parent_id, ...$nationalIds]));
        }

        return array_values(array_unique([(int) $user->party_branch_id, ...$nationalIds]));
    }
}
