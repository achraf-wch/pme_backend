<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\HasApiTokens;

class AuthController extends Controller
{
    // 1. REGISTER - Create a new user account
    public function register(Request $request)
    {
        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the 'visitor' role id (role_id = 1 in our seeder)
        $visitorRole = Role::where('name', 'visitor')->first();

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $visitorRole->id,
            'is_active' => true,
        ]);

        // Generate a Sanctum token for the user
        $token = $user->createToken('auth_token')->plainTextToken;

        // Return user data + token
        return response()->json([
            'message' => 'Registration successful. You are now a visitor.',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    // 2. LOGIN - Authenticate user and issue token
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Revoke old tokens (optional but good practice)
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user->load('role'),  // load role relationship
            'token' => $token
        ]);
    }

    // 3. LOGOUT - Revoke current user's token
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    // 4. GET AUTHENTICATED USER - Returns user info with role
    public function me(Request $request)
    {
        return response()->json($request->user()->load('role'));
    }
}