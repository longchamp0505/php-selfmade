<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('admins')->insert([
            [
                'id' => '9999',
                'password' => Hash::make('999999'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
