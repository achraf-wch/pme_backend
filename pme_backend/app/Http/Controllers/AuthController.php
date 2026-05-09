<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(private NotificationService $notifications)
    {
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['role_id']  = \App\Models\Role::where('name', 'visitor')->first()->id;

        $user  = User::create($data);
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->notifications->notifyAdmins([
            'category' => 'registration',
            'title' => 'Nouvelle inscription',
            'body' => "{$user->name} vient de créer un compte.",
            'action_url' => '/admin/members',
            'action_label' => 'Voir les utilisateurs',
            'source_type' => 'user',
            'source_id' => $user->id,
        ]);

        return response()->json([
            'token' => $token,
            'user'  => $user->load(['role', 'partyBranch']),
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Account is disabled'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user->load(['role', 'partyBranch']),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load(['role', 'partyBranch']));
    }
}
