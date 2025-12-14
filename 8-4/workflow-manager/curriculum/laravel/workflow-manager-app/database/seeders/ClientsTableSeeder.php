<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ClientsTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('clients')->insert([
            [
                'id' => '1001',
                'password' => Hash::make('123456'),
                'company_name' => '株式会社サンプル',
                'department_name' => '営業部',
                'contact' => '03-1234-5678',
                'approver1_name' => '佐藤 次郎',
                'approver1_email' => 'sato@example.com',
                'approver2_name' => '田中 花子',
                'approver2_email' => 'tanaka@example.com',
                'approver3_name' => null,
                'approver3_email' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
