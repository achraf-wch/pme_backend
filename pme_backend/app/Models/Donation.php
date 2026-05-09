<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    protected $fillable = [
        'name', 'email', 'amount', 'note',
        'frequency', 'status', 'payment_reference', 'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
