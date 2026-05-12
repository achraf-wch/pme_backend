<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'sender_id',
        'sender_role',
        'sender_branch_id',
        'recipient_role',
        'recipient_branch_id',
        'title',
        'period_key',
        'period_start',
        'period_end',
        'author_note',
        'summary',
        'pdf_path',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'sent_at' => 'datetime',
        'summary' => 'array',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function senderBranch()
    {
        return $this->belongsTo(PartyBranch::class, 'sender_branch_id');
    }

    public function recipientBranch()
    {
        return $this->belongsTo(PartyBranch::class, 'recipient_branch_id');
    }
}
