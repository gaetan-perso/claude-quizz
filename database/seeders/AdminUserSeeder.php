<?php declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@quiz.local'],
            [
                'name'     => 'Admin Quiz',
                'password' => Hash::make('password'),
                'role'     => UserRole::Admin,
            ]
        );
    }
}
