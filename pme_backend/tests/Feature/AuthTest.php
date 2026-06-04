<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_user_can_register_and_receives_auth_token(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'role'],
            ])
            ->assertJsonPath('user.email', 'test@example.com')
            ->assertJsonPath('user.role.name', 'sympathizer');

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role_id' => Role::where('name', 'sympathizer')->value('id'),
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = $this->createUser('member', [
            'email' => 'member@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'member@example.com',
            'password' => 'correct-password',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'role'],
            ])
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.role.name', 'member');
    }

    public function test_login_rejects_wrong_password(): void
    {
        $this->createUser('member', [
            'email' => 'member@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'member@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid credentials',
            ])
            ->assertJsonMissingPath('token');
    }

    public function test_login_rejects_disabled_account(): void
    {
        $this->createUser('member', [
            'email' => 'disabled@example.com',
            'password' => bcrypt('correct-password'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'disabled@example.com',
            'password' => 'correct-password',
        ]);

        $response
            ->assertForbidden()
            ->assertJson([
                'message' => 'Account is disabled',
            ]);
    }

    public function test_authenticated_user_can_read_me(): void
    {
        $user = $this->createUser('sympathizer');
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this
            ->withToken($token)
            ->getJson('/api/me');

        $response
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', $user->email)
            ->assertJsonPath('role.name', 'sympathizer');
    }

    public function test_logout_revokes_current_access_token(): void
    {
        $user = $this->createUser('member');
        $plainTextToken = $user->createToken('auth_token')->plainTextToken;
        $tokenId = (int) str($plainTextToken)->before('|')->toString();

        $response = $this
            ->withToken($plainTextToken)
            ->postJson('/api/logout');

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Logged out',
            ]);

        $this->assertNull(PersonalAccessToken::find($tokenId));
    }

    public function test_protected_routes_require_authentication(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    private function createUser(string $roleName, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::where('name', $roleName)->value('id'),
        ], $attributes));
    }
}
