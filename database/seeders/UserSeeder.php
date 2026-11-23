<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Diding',
                'role' => Role::Admin,
                'is_active' => true,
                'password' => 'admin123',
            ],
        );

        User::query()->updateOrCreate(
            ['username' => 'cashier'],
            [
                'name' => 'Mika Santos',
                'role' => Role::Cashier,
                'is_active' => true,
                'password' => 'cashier123',
            ],
        );
    }
}
