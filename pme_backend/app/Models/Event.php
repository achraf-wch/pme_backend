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
    public function scopeVisibleTo($query, ?string $role = null)
    {
        return $query->where(function ($q) use ($role) {
            $q->whereNull('audience')->orWhereJsonContains('audience', 'public');
            if ($role) {
                $q->orWhereJsonContains('audience', $role);
            }
        });
    }
}
