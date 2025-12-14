<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'id' => '0001',
                'password' => Hash::make('123456'),
                'name' => '山田 太郎',
                'name_kana' => 'ヤマダ タロウ',
                'hire_date' => '2020-04-01',
                'retire_date' => null,
                'employment_type' => '正社員',
                'contract_type' => 'SES',
                'workplace' => 'A社',
                'scheduled_days' => 5,
                'client_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => '0002',
                'password' => Hash::make('123456'),
                'name' => '鈴木 一郎',
                'name_kana' => 'スズキ イチロウ',
                'hire_date' => '2021-05-10',
                'retire_date' => null,
                'employment_type' => '契約社員',
                'contract_type' => '自社',
                'workplace' => '自社',
                'scheduled_days' => 3,
                'client_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
