<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\User::create([
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'password' => 'password',
            'remember_token' => Str::random(10),
        ]);
        \App\Models\User::create([
            'name' => 'Jane Doe',
            'email' => 'janedoe@example.com',
            'password' => 'password',
            'remember_token' => Str::random(10),
        ]);
        \App\Models\User::factory(10)->create();
    }
}
