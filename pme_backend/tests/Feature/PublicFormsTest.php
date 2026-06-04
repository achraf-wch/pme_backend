<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PublicFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    public function test_contact_form_creates_message(): void
    {
        $response = $this->postJson('/api/contact', [
            'name' => 'Visitor Name',
            'email' => 'visitor@example.com',
            'message' => 'I want to know more about the party program.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('email', 'visitor@example.com')
            ->assertJsonPath('message', 'I want to know more about the party program.');

        $this->assertDatabaseHas('contacts', [
            'name' => 'Visitor Name',
            'email' => 'visitor@example.com',
        ]);
    }

    public function test_contact_form_validates_required_fields(): void
    {
        $response = $this->postJson('/api/contact', [
            'name' => '',
            'email' => 'not-an-email',
            'message' => '',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'message']);
    }

    public function test_newsletter_subscribe_creates_subscriber(): void
    {
        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => 'reader@example.com',
        ]);

        $response
            ->assertCreated()
            ->assertJson([
                'message' => 'Subscribed successfully',
            ]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'reader@example.com',
        ]);
    }

    public function test_newsletter_subscribe_rejects_duplicate_email(): void
    {
        NewsletterSubscriber::create([
            'email' => 'reader@example.com',
        ]);

        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => 'reader@example.com',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_donation_form_creates_pending_donation_with_reference(): void
    {
        $response = $this->postJson('/api/donations', [
            'name' => 'Donor Name',
            'email' => 'donor@example.com',
            'amount' => 250,
            'rib' => '123456789012345678901234',
            'frequency' => 'monthly',
            'note' => 'For campaign support.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('name', 'Donor Name')
            ->assertJsonPath('email', 'donor@example.com')
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('frequency', 'monthly');

        $this->assertDatabaseHas('donations', [
            'name' => 'Donor Name',
            'email' => 'donor@example.com',
            'amount' => 250,
            'status' => 'pending',
            'frequency' => 'monthly',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'donation.created',
        ]);
    }

    public function test_donation_form_accepts_legacy_donor_field_names(): void
    {
        $response = $this->postJson('/api/donations', [
            'donor_name' => 'Legacy Donor',
            'donor_email' => 'legacy@example.com',
            'amount' => 100,
            'rib' => '987654321098765432109876',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('name', 'Legacy Donor')
            ->assertJsonPath('email', 'legacy@example.com')
            ->assertJsonPath('frequency', 'once');

        $this->assertDatabaseHas('donations', [
            'name' => 'Legacy Donor',
            'email' => 'legacy@example.com',
            'frequency' => 'once',
        ]);
    }

    public function test_donation_form_requires_donor_identity(): void
    {
        $response = $this->postJson('/api/donations', [
            'amount' => 50,
            'rib' => '123456789012345678901234',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'Name and email are required.',
            ]);
    }

    public function test_donation_form_validates_amount_and_rib(): void
    {
        $response = $this->postJson('/api/donations', [
            'name' => 'Donor Name',
            'email' => 'donor@example.com',
            'amount' => 0,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount', 'rib']);
    }
}
