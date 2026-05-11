<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipRequest extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'review_stage',
        'motivation',
        'country',
        'regional_branch_id',
        'local_branch_id',
        'age',
        'sex',
        'central_reviewed_by',
        'central_reviewed_at',
        'super_reviewed_by',
        'super_reviewed_at',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'central_reviewed_at' => 'datetime',
        'super_reviewed_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function centralReviewer()
    {
        return $this->belongsTo(User::class, 'central_reviewed_by');
    }

    public function superReviewer()
    {
        return $this->belongsTo(User::class, 'super_reviewed_by');
    }

    public function regionalBranch()
    {
        return $this->belongsTo(PartyBranch::class, 'regional_branch_id');
    }

    public function localBranch()
    {
        return $this->belongsTo(PartyBranch::class, 'local_branch_id');
    }
}
