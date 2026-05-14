<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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
        $data['role_id']  = \App\Models\Role::where('name', 'sympathizer')->first()->id;

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

    public function googleCallback(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string',
        ]);

        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect_uri');

        if (!$clientId || !$clientSecret || !$redirectUri) {
            return response()->json(['message' => 'Google authentication is not configured.'], 500);
        }

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $data['code'],
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);

        if (!$tokenResponse->successful()) {
            return response()->json(['message' => 'Unable to verify Google login.'], 422);
        }

        $accessToken = $tokenResponse->json('access_token');
        $googleUserResponse = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if (!$googleUserResponse->successful() || !$googleUserResponse->json('email')) {
            return response()->json(['message' => 'Unable to read Google profile.'], 422);
        }

        $googleUser = $googleUserResponse->json();
        $roleId = \App\Models\Role::where('name', 'sympathizer')->value('id');

        $user = User::firstOrCreate(
            ['email' => $googleUser['email']],
            [
                'name' => $googleUser['name'] ?? Str::before($googleUser['email'], '@'),
                'password' => Hash::make(Str::random(48)),
                'role_id' => $roleId,
                'is_active' => true,
            ]
        );

        if (!$user->is_active) {
            return response()->json(['message' => 'Account is disabled'], 403);
        }

        $token = $user->createToken('google_auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->load(['role', 'partyBranch']),
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
