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

    public function userCanVote($user): bool
    {
        if (!$user || !$user->role) {
            return false;
        }

        $audience = $this->target_audience ?? [];

        return in_array('public', $audience, true) || in_array($user->role->name, $audience, true);
    }

    /**
     * Scope: active polls visible to a given role (or public).
     */
    public function scopeVisibleTo($query, ?string $role = null)
    {
        return $query->where(function ($q) use ($role) {
            $q->whereNull('target_audience')->orWhereJsonContains('target_audience', 'public');
            if ($role) {
                $q->orWhereJsonContains('target_audience', $role);
            }
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
}
