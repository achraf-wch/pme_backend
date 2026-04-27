<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Event extends Model {
    protected $fillable = ['title', 'description', 'location', 'start_time', 'end_time', 'max_attendees', 'created_by'];
    protected $casts = ['start_time' => 'datetime', 'end_time' => 'datetime'];
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function registrations() { return $this->hasMany(EventRegistration::class); }
}