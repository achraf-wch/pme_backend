<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    protected $fillable = ['donor_name', 'donor_email', 'amount', 'status', 'payment_reference', 'user_id'];
}