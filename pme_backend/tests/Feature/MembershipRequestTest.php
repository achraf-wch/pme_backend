<?php

namespace Tests\Feature;

use App\Models\MembershipRequest;
use App\Models\PartyBranch;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MembershipRequestTest extends TestCase
{
    use RefreshDatabase;

    protected PartyBranch $regionalBranch;

    protected PartyBranch $localBranch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Notification::fake();

        $this->regionalBranch = PartyBranch::create([
            'name' => 'Casablanca Region',
            'type' => 'regional',
            'region' => 'Casablanca-Settat',
        ]);

        $this->localBranch = PartyBranch::create([
            'name' => 'Ain Chock Local',
            'type' => 'local',
            'parent_id' => $this->regionalBranch->id,
            'city' => 'Casablanca',
        ]);
    }

    public function test_authenticated_user_can_submit_membership_request(): void
    {
        $user = $this->createUser('sympathizer');

        $response = $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/membership-request', $this->validPayload());

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Membership request submitted. An admin will review it.')
            ->assertJsonPath('request.status', 'pending')
            ->assertJsonPath('request.review_stage', 'pending')
            ->assertJsonPath('request.regional_branch.id', $this->regionalBranch->id)
            ->assertJsonPath('request.local_branch.id', $this->localBranch->id);

        $this->assertDatabaseHas('membership_requests', [
            'user_id' => $user->id,
            'country' => 'Morocco',
            'regional_branch_id' => $this->regionalBranch->id,
            'local_branch_id' => $this->localBranch->id,
            'age' => 29,
            'sex' => 'male',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'membership_request.created',
        ]);
    }

    public function test_membership_request_requires_authentication(): void
    {
        $this
            ->postJson('/api/membership-request', $this->validPayload())
            ->assertUnauthorized();
    }

    public function test_membership_request_validates_required_profile_fields(): void
    {
        $user = $this->createUser('sympathizer');

        $response = $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/membership-request', [
                'country' => '',
                'regional_branch_id' => null,
                'local_branch_id' => null,
                'age' => 15,
                'sex' => 'invalid',
            ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'country',
                'regional_branch_id',
                'local_branch_id',
                'age',
                'sex',
            ]);
    }

    public function test_user_cannot_submit_duplicate_pending_membership_request(): void
    {
        $user = $this->createUser('sympathizer');

        MembershipRequest::create(array_merge($this->validPayload(), [
            'user_id' => $user->id,
            'status' => 'pending',
            'review_stage' => 'pending',
        ]));

        $response = $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/membership-request', $this->validPayload([
                'motivation' => 'Trying again.',
            ]));

        $response
            ->assertStatus(409)
            ->assertJsonPath('message', 'You already have a membership request.');
    }

    public function test_existing_member_cannot_submit_membership_request(): void
    {
        $member = $this->createUser('member');

        $response = $this
            ->actingAs($member, 'sanctum')
            ->postJson('/api/membership-request', $this->validPayload());

        $response
            ->assertBadRequest()
            ->assertJson([
                'message' => 'You are already a member.',
            ]);
    }

    public function test_central_admin_can_approve_membership_request(): void
    {
        $user = $this->createUser('sympathizer');
        $admin = $this->createUser('central_admin');
        $membershipRequest = MembershipRequest::create(array_merge($this->validPayload(), [
            'user_id' => $user->id,
            'status' => 'pending',
            'review_stage' => 'pending',
        ]));

        $response = $this
            ->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/membership-requests/{$membershipRequest->id}/approve");

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Membership approved. User is now a member.')
            ->assertJsonPath('request.status', 'approved')
            ->assertJsonPath('request.review_stage', 'completed');

        $user->refresh();

        $this->assertSame('member', $user->role->name);
        $this->assertSame($this->localBranch->id, $user->party_branch_id);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'membership_request.approved',
        ]);
    }

    public function test_non_admin_cannot_approve_membership_request(): void
    {
        $user = $this->createUser('sympathizer');
        $membershipRequest = MembershipRequest::create(array_merge($this->validPayload(), [
            'user_id' => $user->id,
            'status' => 'pending',
            'review_stage' => 'pending',
        ]));

        $response = $this
            ->actingAs($user, 'sanctum')
            ->putJson("/api/admin/membership-requests/{$membershipRequest->id}/approve");

        $response->assertForbidden();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'motivation' => 'I want to participate locally.',
            'country' => 'Morocco',
            'regional_branch_id' => $this->regionalBranch->id,
            'local_branch_id' => $this->localBranch->id,
            'age' => 29,
            'sex' => 'male',
        ], $overrides);
    }

    private function createUser(string $roleName, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::where('name', $roleName)->value('id'),
        ], $attributes));
    }
}
