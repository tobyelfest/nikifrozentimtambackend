<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // =========================
        // BRANCHES
        // =========================

        $jogja = Branch::firstOrCreate(
            ['name' => 'Cabang Yogyakarta'],
            [
                'address' => 'Yogyakarta',
                'phone' => '081234567890',
            ]
        );

        $sleman = Branch::firstOrCreate(
            ['name' => 'Cabang Sleman'],
            [
                'address' => 'Sleman, Yogyakarta',
                'phone' => '081234567891',
            ]
        );

        // =========================
        // USERS
        // =========================

        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin',
                'password' => Hash::make('123456'),
                'role' => 'admin',
                'branch_id' => null,
            ]
        );

        User::updateOrCreate(
            ['username' => 'kasirjogja'],
            [
                'name' => 'Kasir Yogyakarta',
                'password' => Hash::make('123456'),
                'role' => 'kasir',
                'branch_id' => $jogja->id,
            ]
        );

        User::updateOrCreate(
            ['username' => 'kasirsleman'],
            [
                'name' => 'Kasir Sleman',
                'password' => Hash::make('123456'),
                'role' => 'kasir',
                'branch_id' => $sleman->id,
            ]
        );
    }
}