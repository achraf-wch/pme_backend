<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Poll extends Model
{
   protected $fillable = [
    'title', 'description', 'start_date', 'end_date', 
    'is_secret', 'created_by', 'target_audience'
];

protected $casts = [
    'start_date' => 'datetime',
    'end_date' => 'datetime',
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

        return in_array($user->role->name, $this->target_audience ?? []);
    }
}