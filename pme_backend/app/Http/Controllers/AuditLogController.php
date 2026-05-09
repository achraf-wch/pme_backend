<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'action' => 'nullable|string|max:120',
        ]);

        $logs = AuditLog::with('user:id,name,email')
            ->when($data['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->latest()
            ->limit(200)
            ->get();

        return response()->json($logs);
    }
}
