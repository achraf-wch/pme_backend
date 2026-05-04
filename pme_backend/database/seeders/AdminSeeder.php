<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\PartyBranch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        $national = PartyBranch::firstOrCreate(
            ['name' => 'Direction Nationale'],
            ['type' => 'national', 'region' => 'Maroc']
        );
        $rabat = PartyBranch::firstOrCreate(
            ['name' => 'Région Rabat-Salé-Kénitra'],
            ['type' => 'regional', 'parent_id' => $national->id, 'region' => 'Rabat-Salé-Kénitra']
        );
        $casa = PartyBranch::firstOrCreate(
            ['name' => 'Région Casablanca-Settat'],
            ['type' => 'regional', 'parent_id' => $national->id, 'region' => 'Casablanca-Settat']
        );
        $rabatLocal = PartyBranch::firstOrCreate(
            ['name' => 'Section locale Rabat'],
            ['type' => 'local', 'parent_id' => $rabat->id, 'city' => 'Rabat', 'region' => 'Rabat-Salé-Kénitra']
        );
        $casaLocal = PartyBranch::firstOrCreate(
            ['name' => 'Section locale Casablanca'],
            ['type' => 'local', 'parent_id' => $casa->id, 'city' => 'Casablanca', 'region' => 'Casablanca-Settat']
        );

        $accounts = [
            ['role' => 'super_admin', 'name' => 'Super Admin', 'email' => 'super.admin@example.com', 'branch_id' => $national->id],
            ['role' => 'admin', 'name' => 'Administrateur Général', 'email' => 'admin@example.com', 'branch_id' => $national->id],
            ['role' => 'central_admin', 'name' => 'Administration Centrale', 'email' => 'central.admin@example.com', 'branch_id' => $national->id],
            ['role' => 'regional_official', 'name' => 'Responsable Régional', 'email' => 'regional@example.com', 'branch_id' => $rabat->id],
            ['role' => 'local_official', 'name' => 'Responsable Local', 'email' => 'local@example.com', 'branch_id' => $rabatLocal->id],
            ['role' => 'member', 'name' => 'Membre Actif', 'email' => 'member@example.com', 'branch_id' => $rabatLocal->id],
            ['role' => 'volunteer', 'name' => 'Bénévole', 'email' => 'volunteer@example.com', 'branch_id' => $casaLocal->id],
            ['role' => 'sympathizer', 'name' => 'Sympathisant', 'email' => 'sympathizer@example.com', 'branch_id' => $rabatLocal->id],
            ['role' => 'visitor', 'name' => 'Visiteur Inscrit', 'email' => 'visitor@example.com', 'branch_id' => $casaLocal->id],
        ];

        foreach ($accounts as $account) {
            $role = Role::firstOrCreate(['name' => $account['role']]);

            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make('password123'),
                    'role_id' => $role->id,
                    'party_branch_id' => $account['branch_id'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Demo accounts created for all platform roles. Password: password123');
    }
}
