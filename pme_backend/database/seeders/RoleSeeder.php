<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        foreach (['visitor', 'sympathizer', 'volunteer', 'member', 'local_official', 'regional_official', 'central_admin', 'admin', 'super_admin'] as $name) {
            Role::firstOrCreate(['name' => $name]);
        }
    }
}
