<?php

namespace App\Http\Controllers;

use App\Models\PartyBranch;

class BranchController extends Controller
{
    public function index()
    {
        return response()->json(
            PartyBranch::with('parent')
                ->orderByRaw("FIELD(type, 'national', 'regional', 'local')")
                ->orderBy('name')
                ->get()
        );
    }
}
