<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventRecap extends Model
{
    protected $fillable = [
        'event_id',
        'created_by',
        'title',
        'content',
        'photos',
    ];

    protected $casts = [
        'photos' => 'array',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
