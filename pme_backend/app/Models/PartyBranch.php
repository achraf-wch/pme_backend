<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartyBranch extends Model
{
    protected $fillable = [
        'name',
        'type',
        'parent_id',
        'city',
        'region',
    ];

    public function parent()
    {
        return $this->belongsTo(PartyBranch::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(PartyBranch::class, 'parent_id');
    }
}
