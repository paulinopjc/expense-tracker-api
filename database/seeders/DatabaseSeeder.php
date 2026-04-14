<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'paulinopjc@gmail.com'],
            [
                'name'      => 'Paulino Awino',
                'password'  => null,
                'role'      => UserRole::Admin,
                'is_active' => true,
                'team_id'   => null,
            ]
        );
    }
}
