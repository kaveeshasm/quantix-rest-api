<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $manager = User::factory()->manager()->create([
            'name' => 'Mary Manager',
            'email' => 'manager@example.com',
        ]);

        User::factory()->reportingTo($manager)->create([
            'name' => 'Alice Employee',
            'email' => 'alice@example.com',
        ]);

        User::factory()->reportingTo($manager)->create([
            'name' => 'Bob Employee',
            'email' => 'bob@example.com',
        ]);

        User::factory()->operations()->create([
            'name' => 'Omar Operations',
            'email' => 'ops@example.com',
        ]);

        User::factory()->operations()->create([
            'name' => 'Olivia Operations',
            'email' => 'ops2@example.com',
        ]);
    }
}
