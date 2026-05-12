<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\PartyBranch;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $nationalBranch = PartyBranch::firstOrCreate(
            ['name' => 'Direction Nationale'],
            ['type' => 'national', 'region' => 'Maroc']
        );
        $rabatBranch = PartyBranch::firstOrCreate(
            ['name' => 'Région Rabat-Salé-Kénitra'],
            ['type' => 'regional', 'parent_id' => $nationalBranch->id, 'region' => 'Rabat-Salé-Kénitra']
        );
        $casaBranch = PartyBranch::firstOrCreate(
            ['name' => 'Région Casablanca-Settat'],
            ['type' => 'regional', 'parent_id' => $nationalBranch->id, 'region' => 'Casablanca-Settat']
        );
        $rabatLocalBranch = PartyBranch::firstOrCreate(
            ['name' => 'Section locale Rabat'],
            ['type' => 'local', 'parent_id' => $rabatBranch->id, 'city' => 'Rabat', 'region' => 'Rabat-Salé-Kénitra']
        );
        $casaLocalBranch = PartyBranch::firstOrCreate(
            ['name' => 'Section locale Casablanca'],
            ['type' => 'local', 'parent_id' => $casaBranch->id, 'city' => 'Casablanca', 'region' => 'Casablanca-Settat']
        );
        $regionalBranches = collect([$rabatBranch, $casaBranch]);
        $localBranches = collect([$rabatLocalBranch, $casaLocalBranch]);

        $branchFixtures = [
            'Fès-Meknès' => ['Fès', 'Meknès', 'Ifrane'],
            'Marrakech-Safi' => ['Marrakech', 'Safi', 'Essaouira'],
            'Tanger-Tétouan-Al Hoceïma' => ['Tanger', 'Tétouan', 'Al Hoceïma'],
            'Souss-Massa' => ['Agadir', 'Taroudant', 'Tiznit'],
            'Oriental' => ['Oujda', 'Nador', 'Berkane'],
            'Drâa-Tafilalet' => ['Errachidia', 'Ouarzazate', 'Zagora'],
        ];

        foreach ($branchFixtures as $regionName => $cities) {
            $regionalBranch = PartyBranch::firstOrCreate(
                ['name' => "Région {$regionName}"],
                ['type' => 'regional', 'parent_id' => $nationalBranch->id, 'region' => $regionName]
            );
            $regionalBranches->push($regionalBranch);

            foreach ($cities as $city) {
                $localBranches->push(PartyBranch::firstOrCreate(
                    ['name' => "Section locale {$city}"],
                    ['type' => 'local', 'parent_id' => $regionalBranch->id, 'city' => $city, 'region' => $regionName]
                ));
            }
        }

        // -------------------------------------------------------
        // 1. ROLES
        // -------------------------------------------------------
        $roles = [
            ['name' => 'visitor', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'sympathizer', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'volunteer', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'member', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'local_official', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'regional_official', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'central_admin', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
        ];
        DB::table('roles')->insertOrIgnore($roles);

        $legacyAdminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
        $centralAdminRoleId = DB::table('roles')->where('name', 'central_admin')->value('id');
        if ($legacyAdminRoleId && $centralAdminRoleId) {
            DB::table('users')->where('role_id', $legacyAdminRoleId)->update(['role_id' => $centralAdminRoleId]);
            DB::table('roles')->where('id', $legacyAdminRoleId)->delete();
        }

        $roleIds = DB::table('roles')->pluck('id', 'name');

        // -------------------------------------------------------
        // 2. USERS
        // -------------------------------------------------------
        $users = [
            [
                'name'       => 'Visiteur',
                'email'      => 'visitor@gmail.com',
                'password'   => Hash::make('password'),
                'role_id'    => $roleIds['visitor'],
                'party_branch_id' => $casaLocalBranch->id,
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Sympathisant',
                'email'      => 'sympathizer@gmail.com',
                'password'   => Hash::make('password'),
                'role_id'    => $roleIds['sympathizer'],
                'party_branch_id' => $rabatLocalBranch->id,
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Bénévole',
                'email'      => 'volunteer@gmail.com',
                'password'   => Hash::make('password'),
                'role_id'    => $roleIds['volunteer'],
                'party_branch_id' => $casaLocalBranch->id,
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Membre',
                'email'      => 'member@gmail.com',
                'password'   => Hash::make('password'),
                'role_id'    => $roleIds['member'],
                'party_branch_id' => $rabatLocalBranch->id,
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Responsable Local',
                'email'      => 'local_rabat@gmail.com',
                'password'   => Hash::make('password'),
                'role_id'    => $roleIds['local_official'],
                'party_branch_id' => $rabatLocalBranch->id,
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Responsable Régional',
                'email'      => 'region_rabat_sale_kenitra@gmail.com',
                'password'   => Hash::make('password'),
                'role_id'    => $roleIds['regional_official'],
                'party_branch_id' => $rabatBranch->id,
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Administration Centrale',
                'email'      => 'central_admin@gmail.com',
                'password'   => Hash::make('password'),
                'role_id'    => $roleIds['central_admin'],
                'party_branch_id' => $nationalBranch->id,
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Superviseur',
                'email'      => 'super_admin@gmail.com',
                'password'   => Hash::make('password'),
                'role_id'    => $roleIds['super_admin'],
                'party_branch_id' => $nationalBranch->id,
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($regionalBranches as $branch) {
            $slug = Str::slug($branch->region ?: $branch->name, '_');
            if (in_array($branch->id, [$rabatBranch->id], true)) {
                continue;
            }

            $users[] = [
                'name'       => 'Responsable Régional ' . ($branch->region ?: $branch->name),
                'email'      => "region_{$slug}@gmail.com",
                'password'   => Hash::make('password'),
                'role_id'    => $roleIds['regional_official'],
                'party_branch_id' => $branch->id,
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($localBranches as $branch) {
            $slug = Str::slug($branch->city ?: $branch->name, '_');
            if (in_array($branch->id, [$rabatLocalBranch->id], true)) {
                continue;
            }

            $users[] = [
                'name'       => 'Responsable Local ' . ($branch->city ?: $branch->name),
                'email'      => "local_{$slug}@gmail.com",
                'password'   => Hash::make('password'),
                'role_id'    => $roleIds['local_official'],
                'party_branch_id' => $branch->id,
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $memberFirstNames = ['Yassine', 'Imane', 'Mehdi', 'Salma'];
        foreach ($localBranches as $branch) {
            $slug = Str::slug($branch->city ?: $branch->name, '_');
            foreach ($memberFirstNames as $index => $firstName) {
                $users[] = [
                    'name'       => "{$firstName} " . ($branch->city ?: $branch->name),
                    'email'      => sprintf('member%d_%s@gmail.com', $index + 1, $slug),
                    'password'   => Hash::make('password'),
                    'role_id'    => $roleIds['member'],
                    'party_branch_id' => $branch->id,
                    'is_active'  => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach ($users as $seedUser) {
            DB::table('users')->updateOrInsert(
                ['email' => $seedUser['email']],
                $seedUser
            );
        }

        $alice  = DB::table('users')->where('email', 'central_admin@gmail.com')->value('id');
        $bob    = DB::table('users')->where('email', 'member@gmail.com')->value('id');
        $clara  = DB::table('users')->where('email', 'region_rabat_sale_kenitra@gmail.com')->value('id');
        $david  = DB::table('users')->where('email', 'visitor@gmail.com')->value('id');

        // -------------------------------------------------------
        // 3. AUDIT LOGS
        // -------------------------------------------------------
        DB::table('audit_logs')->insert([
            [
                'user_id'      => $alice,
                'action'       => 'login',
                'subject_type' => 'App\\Models\\User',
                'subject_id'   => $alice,
                'metadata'     => json_encode(['note' => 'Successful login']),
                'ip_address'   => '192.168.1.1',
                'user_agent'   => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'user_id'      => $alice,
                'action'       => 'create',
                'subject_type' => 'App\\Models\\News',
                'subject_id'   => 1,
                'metadata'     => json_encode(['title' => 'First news article created']),
                'ip_address'   => '192.168.1.1',
                'user_agent'   => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'user_id'      => $bob,
                'action'       => 'update',
                'subject_type' => 'App\\Models\\Donation',
                'subject_id'   => 1,
                'metadata'     => json_encode(['status_changed' => 'pending -> completed']),
                'ip_address'   => '10.0.0.5',
                'user_agent'   => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'user_id'      => $clara,
                'action'       => 'delete',
                'subject_type' => 'App\\Models\\Comment',
                'subject_id'   => 7,
                'metadata'     => json_encode(['reason' => 'Spam content removed']),
                'ip_address'   => '172.16.0.3',
                'user_agent'   => 'Mozilla/5.0 (X11; Linux x86_64)',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'user_id'      => null,
                'action'       => 'failed_login',
                'subject_type' => null,
                'subject_id'   => null,
                'metadata'     => json_encode(['email' => 'unknown@hacker.com']),
                'ip_address'   => '203.0.113.99',
                'user_agent'   => 'curl/7.68.0',
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);

        // -------------------------------------------------------
        // 4. CONTACTS
        // -------------------------------------------------------
        DB::table('contacts')->insert([
            [
                'name'       => 'John Doe',
                'email'      => 'john.doe@mail.com',
                'message'    => 'Hello, I would like to know more about your organization and how I can get involved.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Fatima Zahra',
                'email'      => 'fatima.zahra@mail.com',
                'message'    => 'Je souhaite obtenir des informations sur vos activités et vos projets à venir.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Youssef El Amrani',
                'email'      => 'youssef@mail.com',
                'message'    => 'Bonjour, j\'ai une question concernant votre programme de bénévolat.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // -------------------------------------------------------
        // 5. DONATIONS
        // -------------------------------------------------------
        DB::table('donations')->insert([
            [
                'name'              => 'Membre',
                'email'             => 'member@gmail.com',
                'amount'            => 150.00,
                'note'              => 'Monthly support donation',
                'status'            => 'completed',
                'payment_reference' => 'PAY-' . strtoupper(Str::random(12)),
                'user_id'           => $bob,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'name'              => 'Anonymous Donor',
                'email'             => 'anonymous@mail.com',
                'amount'            => 500.00,
                'note'              => null,
                'status'            => 'completed',
                'payment_reference' => 'PAY-' . strtoupper(Str::random(12)),
                'user_id'           => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'name'              => 'David Member',
                'email'             => 'david@example.com',
                'amount'            => 75.50,
                'note'              => 'In support of the education program',
                'status'            => 'pending',
                'payment_reference' => null,
                'user_id'           => $david,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'name'              => 'Sara Benali',
                'email'             => 'sara.benali@mail.com',
                'amount'            => 200.00,
                'note'              => 'Keep up the great work!',
                'status'            => 'failed',
                'payment_reference' => 'PAY-' . strtoupper(Str::random(12)),
                'user_id'           => null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ]);

        // -------------------------------------------------------
        // 6. EVENTS
        // -------------------------------------------------------
        DB::table('events')->insert([
            [
                'title'           => 'Annual General Assembly 2026',
                'description'     => 'Our yearly assembly where members vote on key decisions and review the past year\'s achievements.',
                'location'        => 'Salle des conférences, Casablanca',
                'start_time'      => Carbon::now()->addDays(10)->setTime(9, 0),
                'end_time'        => Carbon::now()->addDays(10)->setTime(17, 0),
                'max_attendees'   => 200,
                'audience'        => json_encode(['member', 'regional_official', 'central_admin']),
                'attachment_path' => null,
                'created_by'      => $alice,
                'party_branch_id' => $nationalBranch->id,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'title'           => 'Community Volunteer Day',
                'description'     => 'A day dedicated to community service activities across the city.',
                'location'        => 'Parc Yasmina, Rabat',
                'start_time'      => Carbon::now()->addDays(20)->setTime(8, 30),
                'end_time'        => Carbon::now()->addDays(20)->setTime(14, 0),
                'max_attendees'   => 100,
                'audience'        => json_encode(['member', 'volunteer']),
                'attachment_path' => 'attachments/volunteer_day_2026.pdf',
                'created_by'      => $clara,
                'party_branch_id' => $rabatBranch->id,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'title'           => 'Fundraising Gala Night',
                'description'     => 'An elegant evening to raise funds for our upcoming projects.',
                'location'        => 'Hotel Atlas, Marrakech',
                'start_time'      => Carbon::now()->addDays(35)->setTime(19, 0),
                'end_time'        => Carbon::now()->addDays(35)->setTime(23, 59),
                'max_attendees'   => 50,
                'audience'        => json_encode(['member', 'sympathizer']),
                'attachment_path' => null,
                'created_by'      => $alice,
                'party_branch_id' => $casaLocalBranch->id,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);

        $event1 = DB::table('events')->orderBy('id')->value('id');
        $event2 = DB::table('events')->orderBy('id')->skip(1)->value('id');

        // -------------------------------------------------------
        // 7. EVENT REGISTRATIONS
        // -------------------------------------------------------
        DB::table('event_registrations')->insert([
            ['event_id' => $event1, 'user_id' => $bob,   'registered_at' => now()],
            ['event_id' => $event1, 'user_id' => $clara, 'registered_at' => now()],
            ['event_id' => $event1, 'user_id' => $david, 'registered_at' => now()],
            ['event_id' => $event2, 'user_id' => $bob,   'registered_at' => now()],
            ['event_id' => $event2, 'user_id' => $david, 'registered_at' => now()],
        ]);

        // -------------------------------------------------------
        // 8. MEDIA
        // -------------------------------------------------------
        DB::table('media')->insert([
            [
                'file_name'   => 'banner_home.jpg',
                'file_url'    => 'https://storage.example.com/media/banner_home.jpg',
                'file_type'   => 'image/jpeg',
                'file_size'   => 204800,
                'audience'    => json_encode(['public']),
                'uploaded_by' => $alice,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'file_name'   => 'annual_report_2025.pdf',
                'file_url'    => 'https://storage.example.com/media/annual_report_2025.pdf',
                'file_type'   => 'application/pdf',
                'file_size'   => 1048576,
                'audience'    => json_encode(['member', 'central_admin']),
                'uploaded_by' => $alice,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'file_name'   => 'volunteer_day_photo.png',
                'file_url'    => 'https://storage.example.com/media/volunteer_day_photo.png',
                'file_type'   => 'image/png',
                'file_size'   => 512000,
                'audience'    => json_encode(['volunteer', 'sympathizer', 'member']),
                'uploaded_by' => $clara,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);

        // -------------------------------------------------------
        // 9. MEMBERSHIP REQUESTS
        // -------------------------------------------------------
        DB::table('membership_requests')->insert([
            [
                'user_id'     => $bob,
                'status'      => 'approved',
                'review_stage' => 'completed',
                'motivation'  => 'I want to contribute to the community and help with outreach programs.',
                'reviewed_by' => $alice,
                'reviewed_at' => now()->subDays(5),
                'created_at'  => now()->subDays(10),
                'updated_at'  => now()->subDays(5),
            ],
            [
                'user_id'     => $david,
                'status'      => 'approved',
                'review_stage' => 'completed',
                'motivation'  => 'I have been following your work for two years and believe in your mission.',
                'reviewed_by' => $alice,
                'reviewed_at' => now()->subDays(3),
                'created_at'  => now()->subDays(7),
                'updated_at'  => now()->subDays(3),
            ],
            [
                'user_id'     => DB::table('users')->where('email', 'volunteer@gmail.com')->value('id'),
                'status'      => 'rejected',
                'review_stage' => 'rejected',
                'motivation'  => 'Looking for networking opportunities.',
                'reviewed_by' => $clara,
                'reviewed_at' => now()->subDays(1),
                'created_at'  => now()->subDays(4),
                'updated_at'  => now()->subDays(1),
            ],
        ]);

        // -------------------------------------------------------
        // 10. NEWS
        // -------------------------------------------------------
        DB::table('news')->insert([
            [
                'title'        => 'Our Organization Launches New Education Initiative',
                'content'      => 'We are proud to announce the launch of our new education initiative aimed at providing free digital literacy courses to underprivileged youth across the region. This program will serve over 500 students in its first year.',
                'audience'     => json_encode(['public', 'member']),
                'is_published' => 1,
                'published_at' => now()->subDays(2),
                'image_path'   => 'news/education_initiative.jpg',
                'author_id'    => $alice,
                'created_at'   => now()->subDays(2),
                'updated_at'   => now()->subDays(2),
            ],
            [
                'title'        => 'Annual Report 2025 Now Available',
                'content'      => 'Our annual report for 2025 is now publicly available. It highlights the key achievements, financial summary, and plans for the coming year. Download it from the resources section.',
                'audience'     => json_encode(['public', 'member', 'sympathizer']),
                'is_published' => 1,
                'published_at' => now()->subDays(7),
                'image_path'   => null,
                'author_id'    => $alice,
                'created_at'   => now()->subDays(7),
                'updated_at'   => now()->subDays(7),
            ],
            [
                'title'        => 'Upcoming Gala Night – Save the Date',
                'content'      => 'Mark your calendars! Our annual Fundraising Gala Night will be held next month in Marrakech. Tickets are limited. Members get priority access.',
                'audience'     => json_encode(['member']),
                'is_published' => 0,
                'published_at' => now(),
                'image_path'   => 'news/gala_teaser.jpg',
                'author_id'    => $clara,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);

        // -------------------------------------------------------
        // 11. NEWSLETTER SUBSCRIBERS
        // -------------------------------------------------------
        DB::table('newsletter_subscribers')->insert([
            ['email' => 'newsletter1@mail.com', 'created_at' => now(), 'updated_at' => now()],
            ['email' => 'newsletter2@mail.com', 'created_at' => now(), 'updated_at' => now()],
            ['email' => 'newsletter3@mail.com', 'created_at' => now(), 'updated_at' => now()],
            ['email' => 'member@gmail.com',     'created_at' => now(), 'updated_at' => now()],
            ['email' => 'fatima.zahra@mail.com','created_at' => now(), 'updated_at' => now()],
        ]);

        // -------------------------------------------------------
        // 12. PERSONAL ACCESS TOKENS
        // -------------------------------------------------------
        DB::table('personal_access_tokens')->insert([
            [
                'tokenable_type' => 'App\\Models\\User',
                'tokenable_id'   => $alice,
                'name'           => 'admin-api-token',
                'token'          => hash('sha256', Str::random(40)),
                'abilities'      => '["*"]',
                'last_used_at'   => now()->subHours(2),
                'expires_at'     => now()->addYear(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'tokenable_type' => 'App\\Models\\User',
                'tokenable_id'   => $bob,
                'name'           => 'mobile-app-token',
                'token'          => hash('sha256', Str::random(40)),
                'abilities'      => '["read","create"]',
                'last_used_at'   => now()->subDay(),
                'expires_at'     => now()->addMonths(6),
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);

        // -------------------------------------------------------
        // 13. POLLS
        // -------------------------------------------------------
        DB::table('polls')->insert([
            [
                'title'           => 'What should our next community project be?',
                'description'     => 'Vote for the project you would like us to prioritize in Q3 2026.',
                'start_date'      => now()->subDay(),
                'end_date'        => now()->addDays(14),
                'is_secret'       => 0,
                'target_audience' => json_encode(['member']),
                'created_by'      => $alice,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'title'           => 'Board Election – Preferred Meeting Format',
                'description'     => 'Should board meetings be held in-person, online, or hybrid?',
                'start_date'      => now(),
                'end_date'        => now()->addDays(7),
                'is_secret'       => 1,
                'target_audience' => json_encode(['member', 'regional_official']),
                'created_by'      => $clara,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);

        $poll1 = DB::table('polls')->orderBy('id')->value('id');
        $poll2 = DB::table('polls')->orderBy('id')->skip(1)->value('id');

        // -------------------------------------------------------
        // 14. POLL OPTIONS
        // -------------------------------------------------------
        DB::table('poll_options')->insert([
            // Poll 1
            ['poll_id' => $poll1, 'option_text' => 'Tree planting campaign',      'display_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['poll_id' => $poll1, 'option_text' => 'Free coding workshops',       'display_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['poll_id' => $poll1, 'option_text' => 'Blood donation drive',        'display_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['poll_id' => $poll1, 'option_text' => 'Food distribution to shelters','display_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            // Poll 2
            ['poll_id' => $poll2, 'option_text' => 'In-person',                  'display_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['poll_id' => $poll2, 'option_text' => 'Online (video call)',         'display_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['poll_id' => $poll2, 'option_text' => 'Hybrid',                     'display_order' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $opt1 = DB::table('poll_options')->where('poll_id', $poll1)->orderBy('id')->skip(0)->value('id');
        $opt2 = DB::table('poll_options')->where('poll_id', $poll1)->orderBy('id')->skip(1)->value('id');
        $opt3 = DB::table('poll_options')->where('poll_id', $poll1)->orderBy('id')->skip(2)->value('id');
        $opt5 = DB::table('poll_options')->where('poll_id', $poll2)->orderBy('id')->skip(0)->value('id');
        $opt7 = DB::table('poll_options')->where('poll_id', $poll2)->orderBy('id')->skip(2)->value('id');

        // -------------------------------------------------------
        // 15. VOTES
        // -------------------------------------------------------
        DB::table('votes')->insert([
            ['poll_id' => $poll1, 'option_id' => $opt1, 'user_id' => $bob,   'voted_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['poll_id' => $poll1, 'option_id' => $opt2, 'user_id' => $clara, 'voted_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['poll_id' => $poll1, 'option_id' => $opt2, 'user_id' => $david, 'voted_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['poll_id' => $poll1, 'option_id' => $opt3, 'user_id' => $alice, 'voted_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['poll_id' => $poll2, 'option_id' => $opt7, 'user_id' => $alice, 'voted_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['poll_id' => $poll2, 'option_id' => $opt5, 'user_id' => $bob,   'voted_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        // -------------------------------------------------------
        // 16. STATIC PAGES
        // -------------------------------------------------------
        foreach ([
            [
                'slug'             => 'about-us',
                'title'            => 'About Us | من نحن',
                'content'          => '<h1>About Our Organization</h1><p>Parti du Maroc Émergent is a civic political organization focused on training, innovation, solidarity, and responsible public participation.</p>',
                'meta_title'       => 'About Us – Our Mission & Vision',
                'meta_description' => 'Learn about the party, its mission, and its civic engagement.',
            ],
            [
                'slug'             => 'vision-mission',
                'title'            => 'Vision et Mission | الرؤية والرسالة',
                'content'          => '<h1>الرؤية والرسالة</h1><p>رؤيتنا هي بناء ممارسة سياسية حديثة، شفافة، قريبة من المواطن، قائمة على التكوين والابتكار والذكاء الجماعي. رسالتنا هي تحويل هذه الرؤية إلى برامج قابلة للتنفيذ داخل المؤسسات والمجتمع.</p>',
                'meta_title'       => 'Vision et Mission',
                'meta_description' => 'The party vision, mission, and institutional values.',
            ],
            [
                'slug'             => 'leadership-structures',
                'title'            => 'Leadership et Structures | القيادة والهياكل',
                'content'          => '<h1>القيادة والهياكل</h1><p>تعرض هذه الصفحة القيادة الوطنية والهياكل الجهوية والمحلية ولجان العمل الموضوعاتية، مع قابلية التحيين من لوحة الإدارة.</p>',
                'meta_title'       => 'Leadership et Structures',
                'meta_description' => 'National, regional, local, and thematic party structures.',
            ],
            [
                'slug'             => 'social-project',
                'title'            => 'Projet Sociétal | المشروع المجتمعي',
                'content'          => '<h1>برنامج الحزب ومشروعه المجتمعي</h1><p>يركز المشروع المجتمعي على التكوين، التشغيل، التحول الرقمي، العدالة الاجتماعية، مشاركة الشباب، والفعالية المؤسساتية.</p>',
                'meta_title'       => 'Projet Sociétal du PME',
                'meta_description' => 'Political program and social project priorities.',
            ],
            [
                'slug'             => 'privacy-policy',
                'title'            => 'Privacy Policy | سياسة الخصوصية',
                'content'          => '<h1>Privacy Policy</h1><p>We respect your privacy. This page describes how we collect, use, and protect personal data in compliance with applicable laws.</p>',
                'meta_title'       => 'Privacy Policy',
                'meta_description' => 'Read how personal information is handled.',
            ],
            [
                'slug'             => 'terms-of-use',
                'title'            => 'Terms of Use | شروط الاستعمال',
                'content'          => '<h1>Terms of Use</h1><p>These terms define the rules for using the public site, member space, event registration, donations, and digital services.</p>',
                'meta_title'       => 'Terms of Use',
                'meta_description' => 'Rules and conditions for using the platform.',
            ],
            [
                'slug'             => 'accessibility',
                'title'            => 'Accessibility | الولوجية',
                'content'          => '<h1>Accessibility</h1><p>The platform is designed to support accessible navigation, readable content, keyboard use, and inclusive access to public information.</p>',
                'meta_title'       => 'Accessibility',
                'meta_description' => 'Accessibility statement for the public website.',
            ],
            [
                'slug'             => 'faq',
                'title'            => 'Frequently Asked Questions | الأسئلة الشائعة',
                'content'          => '<h1>FAQ</h1><p>Find answers about membership, sympathizer requests, volunteering, donations, events, voting eligibility, and account access.</p>',
                'meta_title'       => 'FAQ – Common Questions Answered',
                'meta_description' => 'Common questions about membership, donations, events, and voting.',
            ],
        ] as $page) {
            DB::table('static_pages')->updateOrInsert(
                ['slug' => $page['slug']],
                array_merge($page, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // -------------------------------------------------------
        // 17. SYMPATHIZERS
        // -------------------------------------------------------
        DB::table('sympathizers')->insert([
            [
                'name'       => 'Karim Tazi',
                'email'      => 'karim.tazi@mail.com',
                'phone'      => '+212 6 12 34 56 78',
                'city'       => 'Casablanca',
                'message'    => 'Je soutiens pleinement votre démarche et souhaite être tenu informé de vos activités.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Nadia Bouchaib',
                'email'      => 'nadia.bouchaib@mail.com',
                'phone'      => null,
                'city'       => 'Fès',
                'message'    => 'Excellent travail ! Continuez à œuvrer pour le bien commun.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Omar Lamrani',
                'email'      => 'omar.lamrani@mail.com',
                'phone'      => '+212 6 98 76 54 32',
                'city'       => 'Rabat',
                'message'    => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // -------------------------------------------------------
        // 18. VOLUNTEERS
        // -------------------------------------------------------
        DB::table('volunteers')->insert([
            [
                'name'       => 'Amine Cherkaoui',
                'email'      => 'amine.cherkaoui@mail.com',
                'phone'      => '+212 6 55 44 33 22',
                'city'       => 'Marrakech',
                'skills'     => 'Web development, graphic design, social media management',
                'motivation' => 'I want to use my technical skills to help your digital presence grow.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Houda Mansouri',
                'email'      => 'houda.mansouri@mail.com',
                'phone'      => null,
                'city'       => 'Agadir',
                'skills'     => 'Teaching, curriculum design, community outreach',
                'motivation' => 'Education is a right. I want to help deliver free courses to underserved areas.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Tariq El Filali',
                'email'      => 'tariq.elfilali@mail.com',
                'phone'      => '+212 6 22 11 00 99',
                'city'       => 'Meknes',
                'skills'     => 'Event coordination, logistics, fundraising',
                'motivation' => 'I have organized 3 charity events before and would love to bring my experience to your team.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
