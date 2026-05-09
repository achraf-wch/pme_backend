<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Media extends Model {
    protected $fillable = ['file_name', 'file_url', 'file_type', 'file_size', 'uploaded_by', 'audience'];
    protected $casts = ['audience' => 'array'];

    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function scopeVisibleTo($query, ?string $role = null)
    {
        return $query->where(function ($q) use ($role) {
            $q->whereNull('audience')->orWhereJsonContains('audience', 'public');
            if ($role) {
                $q->orWhereJsonContains('audience', $role);
            }
        });
    }
}
