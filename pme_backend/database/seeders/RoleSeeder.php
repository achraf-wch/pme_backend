<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        Role::create(['name' => 'visitor']);
        Role::create(['name' => 'member']);
        Role::create(['name' => 'admin']);
    }
}