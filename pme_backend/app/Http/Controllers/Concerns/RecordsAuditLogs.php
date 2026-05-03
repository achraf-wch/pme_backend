<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AuditLog;
use Illuminate\Http\Request;

trait RecordsAuditLogs
{
    protected function audit(Request $request, string $action, $subject = null, array $metadata = []): void
    {
        AuditLog::create([
            'user_id' => optional($request->user())->id,
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'metadata' => $metadata ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
        ]);
    }
}
