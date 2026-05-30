<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'email', 'amount', 'rib', 'note',
        'frequency', 'status', 'payment_reference', 'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
