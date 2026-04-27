<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role_id', 'is_active',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function membershipRequest()
    {
        return $this->hasOne(MembershipRequest::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }

    public function news()
    {
        return $this->hasMany(News::class, 'author_id');
    }
}