<?php

namespace Mtr\TestChat\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestChatUsersSeeder extends Seeder
{
    /**
     * Seed a small set of chat demo users.
     */
    public function run(): void
    {
        $now = now();
        $passwordHash = Hash::make('password');

        $users = [
            [
                'id' => 50,
                'name' => 'Victor Galitsky',
                'email' => 'concept.galitsky@gmail.com',
                'remember_token' => '',
            ],
            [
                'id' => 51,
                'name' => 'Oleg Zadunayskyi',
                'email' => 'oleg@test.com',
                'remember_token' => null,
            ],
            [
                'id' => 52,
                'name' => 'Serzh',
                'email' => 'serzh@test.com',
                'remember_token' => null,
            ],
            [
                'id' => 53,
                'name' => 'Ruslan',
                'email' => 'ruslan@test.com',
                'remember_token' => null,
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['id' => $user['id']],
                [
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'email_verified_at' => null,
                    'password' => $passwordHash,
                    'remember_token' => $user['remember_token'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
