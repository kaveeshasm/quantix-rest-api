<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

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
