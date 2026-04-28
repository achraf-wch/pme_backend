<?php
namespace App\Http\Controllers;

use App\Models\Volunteer;
use Illuminate\Http\Request;

class VolunteerController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:volunteers,email',
            'phone'      => 'nullable|string|max:30',
            'city'       => 'nullable|string|max:100',
            'skills'     => 'nullable|string',
            'motivation' => 'nullable|string',
        ]);
        Volunteer::create($data);
        return response()->json(['message' => 'Volunteer request submitted'], 201);
    }

    public function index()
    {
        return response()->json(Volunteer::latest()->get());
    }

    public function destroy($id)
    {
        Volunteer::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}