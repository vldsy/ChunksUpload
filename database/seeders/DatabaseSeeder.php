<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PostSeeder::class,
        ]);

        // Create 2 users
        //User::truncate();
        User::where('id', '>', 0)->delete();

        $password = "12345";

        User::create([
            'name' => "John Doe",
            'email' => "johndoe@gmail.com",
            'email_verified_at' => now(),
            'password' => bcrypt($password),
            'remember_token' => Str::random(10),
        ]);

        User::create([
            'name' => "Bill Gates",
            'email' => "billgates@gmail.com",
            'email_verified_at' => now(),
            'password' => bcrypt($password),
            'remember_token' => Str::random(10),
        ]);
    }
}
