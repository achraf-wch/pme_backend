<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $fillable = [
        'title',
        'content',
        'is_published',
        'published_at',
        'author_id',
        'image_path',
        'audience',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'audience'     => 'array',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Scope: only news visible to a given role (or public).
     */
    public function scopeVisibleTo($query, ?string $role = null)
    {
        return $query->where(function ($q) use ($role) {
            $q->whereJsonContains('audience', 'public');
            if ($role) {
                $q->orWhereJsonContains('audience', $role);
            }
        });
    }
}