<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. ROLES ─────────────────────────────────────────────────────────
        $roles = ['visitor', 'sympathizer', 'volunteer', 'member', 'local_official', 'regional_official', 'central_admin', 'admin', 'super_admin'];
        $roleIds = [];
        foreach ($roles as $role) {
            $existingRole = DB::table('roles')->where('name', $role)->first();
            $roleIds[$role] = $existingRole?->id ?? DB::table('roles')->insertGetId([
                'name' => $role,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ── 2. USERS ──────────────────────────────────────────────────────────

        // Admin
        $adminId = DB::table('users')->insertGetId([
            'name'       => 'Admin Principal',
            'email'      => 'admin@parti.ma',
            'password'   => Hash::make('password'),
            'role_id'    => $roleIds['admin'],
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Members
        $memberIds = [];
        $members = [
            ['name' => 'Amina Tazi',      'email' => 'amina@example.com'],
            ['name' => 'Khalid Ouazzani', 'email' => 'khalid@example.com'],
            ['name' => 'Nadia Berrada',   'email' => 'nadia@example.com'],
            ['name' => 'Omar Chraibi',    'email' => 'omar@example.com'],
            ['name' => 'Samira Alaoui',   'email' => 'samira@example.com'],
        ];
        foreach ($members as $m) {
            $memberIds[] = DB::table('users')->insertGetId([
                'name'       => $m['name'],
                'email'      => $m['email'],
                'password'   => Hash::make('password'),
                'role_id'    => $roleIds['member'],
                'is_active'  => 1,
                'created_at' => now()->subDays(rand(5, 60)),
                'updated_at' => now(),
            ]);
        }

        // Visitors (acting as sympathizers for now)
        $visitorIds = [];
        $visitors = [
            ['name' => 'Rachid Idrissi', 'email' => 'rachid@example.com'],
            ['name' => 'Loubna Sekkat',  'email' => 'loubna@example.com'],
            ['name' => 'Mehdi Bousfiha', 'email' => 'mehdi@example.com'],
        ];
        foreach ($visitors as $v) {
            $visitorIds[] = DB::table('users')->insertGetId([
                'name'       => $v['name'],
                'email'      => $v['email'],
                'password'   => Hash::make('password'),
                'role_id'    => $roleIds['visitor'],
                'is_active'  => 1,
                'created_at' => now()->subDays(rand(1, 10)),
                'updated_at' => now(),
            ]);
        }

        // ── 3. MEMBERSHIP REQUESTS ────────────────────────────────────────────

        // Approved
        DB::table('membership_requests')->insert([
            'user_id'     => $memberIds[0],
            'status'      => 'approved',
            'motivation'  => 'Je souhaite contribuer activement au développement de notre région et défendre les valeurs du parti.',
            'reviewed_by' => $adminId,
            'reviewed_at' => now()->subDays(10),
            'created_at'  => now()->subDays(15),
            'updated_at'  => now()->subDays(10),
        ]);
        DB::table('membership_requests')->insert([
            'user_id'     => $memberIds[1],
            'status'      => 'approved',
            'motivation'  => 'Engagé depuis des années dans le bénévolat, je veux m\'impliquer davantage dans la vie politique locale.',
            'reviewed_by' => $adminId,
            'reviewed_at' => now()->subDays(5),
            'created_at'  => now()->subDays(8),
            'updated_at'  => now()->subDays(5),
        ]);

        // Pending
        DB::table('membership_requests')->insert([
            'user_id'    => $visitorIds[0],
            'status'     => 'pending',
            'motivation' => 'Étudiant en droit, je veux m\'engager pour les droits civiques et la transparence.',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
        DB::table('membership_requests')->insert([
            'user_id'    => $visitorIds[1],
            'status'     => 'pending',
            'motivation' => 'Enseignante engagée, je veux défendre l\'éducation publique.',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        // Rejected
        DB::table('membership_requests')->insert([
            'user_id'     => $visitorIds[2],
            'status'      => 'rejected',
            'motivation'  => 'Test.',
            'reviewed_by' => $adminId,
            'reviewed_at' => now()->subDays(3),
            'created_at'  => now()->subDays(4),
            'updated_at'  => now()->subDays(3),
        ]);

        // ── 4. EVENTS ─────────────────────────────────────────────────────────

        $events = [
            [
                'title'         => 'Congrès National du Parti',
                'description'   => 'Réunion annuelle des membres pour définir les orientations stratégiques et élire les instances dirigeantes pour le mandat à venir.',
                'location'      => 'Palais des Congrès, Casablanca',
                'start_time'    => Carbon::now()->addDays(15)->setTime(9, 0),
                'end_time'      => Carbon::now()->addDays(15)->setTime(18, 0),
                'max_attendees' => 500,
                'audience'      => json_encode(['member', 'admin']),
                'created_by'    => $adminId,
            ],
            [
                'title'         => 'Forum Citoyen : Jeunesse et Politique',
                'description'   => 'Un espace d\'échange ouvert au grand public pour discuter de l\'engagement civique des jeunes et des enjeux de la démocratie participative.',
                'location'      => 'Faculté des Sciences Juridiques, Rabat',
                'start_time'    => Carbon::now()->addDays(7)->setTime(14, 0),
                'end_time'      => Carbon::now()->addDays(7)->setTime(17, 30),
                'max_attendees' => 200,
                'audience'      => json_encode(['public', 'visitor', 'member']),
                'created_by'    => $adminId,
            ],
            [
                'title'         => 'Atelier : Femmes et Leadership Politique',
                'description'   => 'Formation et échanges sur la place des femmes dans la vie politique, animé par des militantes et des élues de terrain.',
                'location'      => 'Centre Culturel, Marrakech',
                'start_time'    => Carbon::now()->subDays(5)->setTime(9, 30),
                'end_time'      => Carbon::now()->subDays(5)->setTime(16, 0),
                'max_attendees' => 80,
                'audience'      => json_encode(['member', 'visitor']),
                'created_by'    => $adminId,
            ],
            [
                'title'         => 'Journée Portes Ouvertes',
                'description'   => 'Venez découvrir notre parti, nos valeurs et nos projets. Rencontrez nos militants et posez toutes vos questions librement.',
                'location'      => 'Siège national du parti, Rabat',
                'start_time'    => Carbon::now()->subDays(20)->setTime(10, 0),
                'end_time'      => Carbon::now()->subDays(20)->setTime(17, 0),
                'max_attendees' => null,
                'audience'      => json_encode(['public']),
                'created_by'    => $adminId,
            ],
            [
                'title'         => 'Réunion de Bureau – Bilan Trimestriel',
                'description'   => 'Réunion réservée aux membres actifs pour faire le point sur les actions menées et préparer le prochain trimestre.',
                'location'      => 'Siège national du parti, Rabat',
                'start_time'    => Carbon::now()->addDays(3)->setTime(10, 0),
                'end_time'      => Carbon::now()->addDays(3)->setTime(13, 0),
                'max_attendees' => 40,
                'audience'      => json_encode(['member', 'admin']),
                'created_by'    => $adminId,
            ],
        ];

        $eventIds = [];
        foreach ($events as $ev) {
            $eventIds[] = DB::table('events')->insertGetId(array_merge($ev, [
                'created_at' => now()->subDays(rand(1, 30)),
                'updated_at' => now(),
            ]));
        }

        // ── 5. EVENT REGISTRATIONS ────────────────────────────────────────────

        $registrations = [
            ['event_id' => $eventIds[0], 'user_id' => $memberIds[0]],
            ['event_id' => $eventIds[0], 'user_id' => $memberIds[1]],
            ['event_id' => $eventIds[0], 'user_id' => $memberIds[2]],
            ['event_id' => $eventIds[1], 'user_id' => $memberIds[3]],
            ['event_id' => $eventIds[1], 'user_id' => $memberIds[4]],
            ['event_id' => $eventIds[1], 'user_id' => $visitorIds[0]],
            ['event_id' => $eventIds[2], 'user_id' => $memberIds[0]],
            ['event_id' => $eventIds[2], 'user_id' => $memberIds[2]],
            ['event_id' => $eventIds[3], 'user_id' => $visitorIds[1]],
            ['event_id' => $eventIds[4], 'user_id' => $memberIds[1]],
            ['event_id' => $eventIds[4], 'user_id' => $memberIds[3]],
        ];
        foreach ($registrations as $reg) {
            DB::table('event_registrations')->insert(array_merge($reg, [
                'registered_at' => now()->subDays(rand(1, 5)),
            ]));
        }

        // ── 6. NEWS ───────────────────────────────────────────────────────────

        $newsItems = [
            [
                'title'        => 'Le parti présente son programme économique pour 2025-2030',
                'content'      => 'Lors d\'une conférence de presse tenue au siège national, le bureau politique a dévoilé un programme ambitieux axé sur la création d\'emplois, le soutien aux PME et la transition énergétique. Ce plan vise à relancer la croissance tout en réduisant les inégalités régionales.',
                'audience'     => json_encode(['public']),
                'is_published' => 1,
                'published_at' => now()->subDays(3),
                'author_id'    => $adminId,
            ],
            [
                'title'        => 'Résultats des élections internes : nouveau bureau exécutif élu',
                'content'      => 'Le congrès extraordinaire tenu le week-end dernier a permis l\'élection du nouveau bureau exécutif du parti. Avec un taux de participation record de 87%, les militants ont plébiscité une liste de renouvellement portée par des profils jeunes et diversifiés.',
                'audience'     => json_encode(['member', 'admin']),
                'is_published' => 1,
                'published_at' => now()->subDays(7),
                'author_id'    => $adminId,
            ],
            [
                'title'        => 'Campagne d\'inscription aux listes électorales',
                'content'      => 'Le parti lance une campagne nationale pour encourager les citoyens à s\'inscrire sur les listes électorales avant la date limite. Des stands d\'information seront installés dans 12 villes du Royaume.',
                'audience'     => json_encode(['public', 'visitor']),
                'is_published' => 1,
                'published_at' => now()->subDays(12),
                'author_id'    => $adminId,
            ],
            [
                'title'        => 'Note interne : directives pour la préparation du congrès',
                'content'      => 'Cette note s\'adresse exclusivement aux membres actifs. Elle contient les directives relatives à la sélection des délégués, au calendrier des réunions préparatoires et aux procédures de vote.',
                'audience'     => json_encode(['member', 'admin']),
                'is_published' => 1,
                'published_at' => now()->subDays(2),
                'author_id'    => $adminId,
            ],
            [
                'title'        => 'Tribune : Pour une politique climatique ambitieuse au Maroc',
                'content'      => 'Face à l\'urgence climatique, notre parti appelle à une révision profonde de la politique environnementale nationale. Le Maroc a les atouts pour devenir un modèle régional en matière de développement durable.',
                'audience'     => json_encode(['public']),
                'is_published' => 1,
                'published_at' => now()->subDays(18),
                'author_id'    => $adminId,
            ],
        ];

        foreach ($newsItems as $item) {
            DB::table('news')->insert(array_merge($item, [
                'created_at' => now()->subDays(rand(1, 25)),
                'updated_at' => now(),
            ]));
        }

        // ── 7. POLLS ──────────────────────────────────────────────────────────

        $polls = [
            [
                'title'           => 'Quelle priorité pour notre programme 2026 ?',
                'description'     => 'Aidez-nous à définir les axes prioritaires du prochain programme électoral.',
                'start_date'      => now()->subDays(5),
                'end_date'        => now()->addDays(10),
                'is_secret'       => 0,
                'target_audience' => json_encode(['member', 'visitor']),
                'created_by'      => $adminId,
                'options'         => ['Éducation et jeunesse', 'Économie et emploi', 'Santé publique', 'Transition écologique'],
            ],
            [
                'title'           => 'Faut-il organiser des primaires ouvertes ?',
                'description'     => 'Souhaitez-vous que le parti adopte un système de primaires ouvertes pour désigner ses candidats ?',
                'start_date'      => now()->subDays(2),
                'end_date'        => now()->addDays(5),
                'is_secret'       => 1,
                'target_audience' => json_encode(['member']),
                'created_by'      => $adminId,
                'options'         => ['Oui, pour tous les scrutins', 'Oui, uniquement pour les législatives', 'Non, réserver aux militants', 'Abstention'],
            ],
            [
                'title'           => 'Satisfaction concernant la communication du parti',
                'description'     => 'Comment évaluez-vous la qualité de la communication interne du parti ces derniers mois ?',
                'start_date'      => now()->subDays(10),
                'end_date'        => now()->subDays(1),
                'is_secret'       => 0,
                'target_audience' => json_encode(['member', 'admin']),
                'created_by'      => $adminId,
                'options'         => ['Très satisfaisante', 'Satisfaisante', 'Insuffisante', 'Très insuffisante'],
            ],
        ];

        $pollIds = [];
        foreach ($polls as $poll) {
            $options = $poll['options'];
            unset($poll['options']);
            $pollId = DB::table('polls')->insertGetId(array_merge($poll, [
                'created_at' => now()->subDays(rand(2, 12)),
                'updated_at' => now(),
            ]));
            $pollIds[] = $pollId;

            foreach ($options as $i => $text) {
                DB::table('poll_options')->insert([
                    'poll_id'       => $pollId,
                    'option_text'   => $text,
                    'display_order' => $i,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }

        // ── 8. VOTES ──────────────────────────────────────────────────────────

        $poll1Options = DB::table('poll_options')->where('poll_id', $pollIds[0])->pluck('id')->toArray();
        foreach ($memberIds as $i => $uid) {
            DB::table('votes')->insert([
                'poll_id'    => $pollIds[0],
                'option_id'  => $poll1Options[$i % count($poll1Options)],
                'user_id'    => $uid,
                'voted_at'   => now()->subDays(rand(1, 4)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $poll3Options = DB::table('poll_options')->where('poll_id', $pollIds[2])->pluck('id')->toArray();
        foreach ([$memberIds[0], $memberIds[1], $memberIds[2]] as $i => $uid) {
            DB::table('votes')->insert([
                'poll_id'    => $pollIds[2],
                'option_id'  => $poll3Options[$i % count($poll3Options)],
                'user_id'    => $uid,
                'voted_at'   => now()->subDays(rand(2, 9)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ── 9. DONATIONS ──────────────────────────────────────────────────────

        $donationsData = [
            ['name' => 'Ahmed Benjelloun', 'email' => 'ahmed.b@example.com', 'amount' => 500.00,  'status' => 'completed', 'note' => 'Soutien au congrès national', 'user_id' => $memberIds[0]],
            ['name' => 'Khadija Filali',   'email' => 'khadija@example.com', 'amount' => 200.00,  'status' => 'completed', 'note' => 'Don mensuel',               'user_id' => $memberIds[1]],
            ['name' => 'Tariq Moussaoui',  'email' => 'tariq@example.com',   'amount' => 1000.00, 'status' => 'completed', 'note' => 'Campagne électorale',       'user_id' => null],
            ['name' => 'Zineb Lahlou',     'email' => 'zineb@example.com',   'amount' => 150.00,  'status' => 'pending',   'note' => null,                       'user_id' => $memberIds[2]],
            ['name' => 'Hassan Benkirane', 'email' => 'hassan@example.com',  'amount' => 300.00,  'status' => 'completed', 'note' => 'Aide à l\'impression',     'user_id' => null],
            ['name' => 'Imane Cherkaoui',  'email' => 'imane@example.com',   'amount' => 75.00,   'status' => 'failed',    'note' => null,                       'user_id' => $visitorIds[0]],
            ['name' => 'Yassine Rhazi',    'email' => 'yassine@example.com', 'amount' => 250.00,  'status' => 'completed', 'note' => 'Fonds de solidarité',      'user_id' => null],
        ];

        foreach ($donationsData as $don) {
            DB::table('donations')->insert(array_merge($don, [
                'payment_reference' => $don['status'] === 'completed' ? 'PAY-' . strtoupper(substr(md5(rand()), 0, 10)) : null,
                'created_at'        => now()->subDays(rand(1, 45)),
                'updated_at'        => now(),
            ]));
        }

        // ── 10. CONTACTS ──────────────────────────────────────────────────────

        $contacts = [
            ['name' => 'Soukaina Amrani', 'email' => 'soukaina@example.com', 'message' => 'Bonjour, je souhaite obtenir des informations sur l\'adhésion au parti. Quelles sont les conditions et les modalités ?'],
            ['name' => 'Badr El Fassi',   'email' => 'badr@example.com',     'message' => 'Je suis journaliste et je souhaite prendre contact avec le service presse pour un entretien sur votre programme économique.'],
            ['name' => 'Nour Benmoussa', 'email'  => 'nour@example.com',     'message' => 'Existe-t-il une section jeunesse dans la région de Tanger-Tétouan ? Je suis intéressé(e) pour m\'engager.'],
            ['name' => 'Amine Kettani',  'email'  => 'amine@example.com',    'message' => 'Je n\'arrive pas à finaliser mon inscription sur le site. Pouvez-vous m\'aider ?'],
            ['name' => 'Dounia Sebti',   'email'  => 'dounia@example.com',   'message' => 'Félicitations pour votre programme sur l\'environnement. Continuez dans cette voie !'],
        ];

        foreach ($contacts as $contact) {
            DB::table('contacts')->insert(array_merge($contact, [
                'created_at' => now()->subDays(rand(1, 20)),
                'updated_at' => now(),
            ]));
        }

        // ── 11. SYMPATHIZERS ─────────────────────────────────────────────────

        $sympathizers = [
            ['name' => 'Laila Berrada',  'email' => 'laila.b@example.com', 'phone' => '+212661234501', 'city' => 'Rabat',      'message' => 'Je partage les valeurs du parti et souhaite être tenu(e) informé(e) des activités.'],
            ['name' => 'Mouad Tahiri',   'email' => 'mouad@example.com',   'phone' => '+212662345602', 'city' => 'Casablanca', 'message' => 'Sympathisant depuis les dernières élections, je veux m\'impliquer davantage.'],
            ['name' => 'Sara Hakimi',    'email' => 'sara.h@example.com',  'phone' => '+212663456703', 'city' => 'Fès',        'message' => 'Étudiante en sciences politiques, je suis les activités du parti avec beaucoup d\'intérêt.'],
            ['name' => 'Anouar Bennis',  'email' => 'anouar@example.com',  'phone' => null,            'city' => 'Meknès',     'message' => null],
            ['name' => 'Hafida Zouiten', 'email' => 'hafida@example.com',  'phone' => '+212665678905', 'city' => 'Agadir',     'message' => 'Commerçante, j\'adhère pleinement au programme économique du parti.'],
        ];

        foreach ($sympathizers as $sym) {
            DB::table('sympathizers')->insert(array_merge($sym, [
                'created_at' => now()->subDays(rand(2, 30)),
                'updated_at' => now(),
            ]));
        }

        // ── 12. VOLUNTEERS ────────────────────────────────────────────────────

        $volunteers = [
            ['name' => 'Ilyas Aziz',       'email' => 'ilyas@example.com',   'phone' => '+212664567801', 'city' => 'Marrakech', 'skills' => 'Communication digitale, réseaux sociaux, graphisme', 'motivation' => 'Je veux mettre mes compétences au service du parti pour renforcer sa présence en ligne.'],
            ['name' => 'Rim Saidi',        'email' => 'rim@example.com',     'phone' => '+212665678902', 'city' => 'Oujda',     'skills' => 'Traduction arabe-français, interprétariat',         'motivation' => 'Bilingue, je peux aider lors des événements et pour la traduction des documents.'],
            ['name' => 'Zakaria El Ouafi', 'email' => 'zakaria@example.com', 'phone' => null,            'city' => 'Rabat',     'skills' => 'Droit, conseil juridique, rédaction',               'motivation' => 'Avocat de formation, je veux contribuer à la rédaction de positions juridiques du parti.'],
            ['name' => 'Meryem Bensouda',  'email' => 'meryem@example.com',  'phone' => '+212667890104', 'city' => 'Tanger',    'skills' => 'Organisation d\'événements, logistique',            'motivation' => 'J\'ai organisé plusieurs événements associatifs et je veux aider lors des rassemblements.'],
        ];

        foreach ($volunteers as $vol) {
            DB::table('volunteers')->insert(array_merge($vol, [
                'created_at' => now()->subDays(rand(1, 25)),
                'updated_at' => now(),
            ]));
        }

        // ── 13. NEWSLETTER SUBSCRIBERS ───────────────────────────────────────

        $subscribers = [
            'newsletter1@example.com',
            'newsletter2@example.com',
            'newsletter3@example.com',
            'newsletter4@example.com',
            'newsletter5@example.com',
            'amina@example.com',
            'khalid@example.com',
            'soukaina@example.com',
        ];

        foreach ($subscribers as $email) {
            DB::table('newsletter_subscribers')->insert([
                'email'      => $email,
                'created_at' => now()->subDays(rand(1, 40)),
                'updated_at' => now(),
            ]);
        }

        // ── 14. STATIC PAGES ──────────────────────────────────────────────────

        $pages = [
            [
                'slug'             => 'a-propos',
                'title'            => 'À propos du parti',
                'content'          => 'Fondé en 2010, notre parti est un mouvement progressiste ancré dans les valeurs de démocratie, de justice sociale et de développement durable. Nous croyons en un Maroc fort, uni et ouvert sur le monde, où chaque citoyen a sa place et sa voix.',
                'meta_title'       => 'À propos – Parti Citoyen',
                'meta_description' => 'Découvrez l\'histoire, les valeurs et les engagements du Parti Citoyen.',
            ],
            [
                'slug'             => 'programme',
                'title'            => 'Notre Programme',
                'content'          => 'Notre programme repose sur cinq piliers : l\'éducation pour tous, la création d\'emplois durables, la transition écologique, la réforme de la gouvernance et la promotion de la culture nationale. Chaque mesure est chiffrée et planifiée sur un horizon de cinq ans.',
                'meta_title'       => 'Programme – Parti Citoyen',
                'meta_description' => 'Consultez le programme détaillé du Parti Citoyen pour les prochaines élections.',
            ],
            [
                'slug'             => 'contact',
                'title'            => 'Contactez-nous',
                'content'          => 'Pour toute question ou demande d\'information, n\'hésitez pas à nous écrire via le formulaire de contact ou à nous rendre visite au siège national, 15 avenue Mohammed V, Rabat. Nos équipes vous répondront dans les 48 heures.',
                'meta_title'       => 'Contact – Parti Citoyen',
                'meta_description' => 'Contactez le Parti Citoyen pour toute question ou demande d\'adhésion.',
            ],
        ];

        foreach ($pages as $page) {
            DB::table('static_pages')->insert(array_merge($page, [
                'created_at' => now()->subDays(rand(5, 30)),
                'updated_at' => now(),
            ]));
        }

        // ── SUMMARY ───────────────────────────────────────────────────────────

        $this->command->info('✅ Seeder terminé avec succès !');
        $this->command->table(
            ['Ressource', 'Détail'],
            [
                ['Rôles (9)',            'visitor | sympathizer | volunteer | member | local_official | regional_official | central_admin | admin | super_admin'],
                ['Admin',               'admin@parti.ma  /  password'],
                ['Membres (5)',         'amina, khalid, nadia, omar, samira @example.com  /  password'],
                ['Visiteurs (3)',       'rachid, loubna, mehdi @example.com  /  password'],
                ['Demandes adhésion',   '5  (2 approuvées · 2 en attente · 1 rejetée)'],
                ['Événements',          '5  (passés + à venir, audiences variées)'],
                ['Inscriptions',        '11'],
                ['Actualités',          '5  (publiques + membres seulement)'],
                ['Sondages',            '3  (actif · secret · clôturé)  +  8 votes'],
                ['Dons',                '7  (completed · pending · failed)'],
                ['Contacts',            '5'],
                ['Sympathisants',       '5'],
                ['Bénévoles',           '4'],
                ['Newsletter',          '8 abonnés'],
                ['Pages statiques',     '3'],
            ]
        );
    }
}
