<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Sympathizer extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'city', 'message'];
}