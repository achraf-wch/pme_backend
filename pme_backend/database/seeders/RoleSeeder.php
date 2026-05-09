<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;

class RoleSeeder extends Seeder
{
    public function run()
    {
        foreach (['visitor', 'sympathizer', 'volunteer', 'member', 'local_official', 'regional_official', 'central_admin', 'super_admin'] as $name) {
            Role::firstOrCreate(['name' => $name]);
        }

        $centralAdmin = Role::where('name', 'central_admin')->first();
        $legacyAdmin = Role::where('name', 'admin')->first();

        if ($centralAdmin && $legacyAdmin) {
            User::where('role_id', $legacyAdmin->id)->update(['role_id' => $centralAdmin->id]);
            $legacyAdmin->delete();
        }
    }
}
