<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PollOption extends Model
{
    protected $fillable = ['poll_id', 'option_text', 'display_order'];

    public function poll()
    {
        return $this->belongsTo(Poll::class);
    }
public function votes()
{
    return $this->hasMany(Vote::class, 'option_id');
}
}