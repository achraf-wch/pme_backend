<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index()
    {
        return response()->json(
            User::whereHas('role', fn($q) => $q->where('name', 'member'))
                ->with('role')
                ->latest()
                ->get()
        );
    }

    public function show($id)
    {
        return response()->json(User::with('role')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name'    => 'sometimes|string|max:255',
            'email'   => 'sometimes|email|unique:users,email,' . $id,
            'role_id' => 'sometimes|exists:roles,id',
        ]);

        $user->update($data);
        return response()->json($user->load('role'));
    }

    public function destroy($id)
    {
        User::findOrFail($id)->delete();
        return response()->json(['message' => 'Member deleted']);
    }
}