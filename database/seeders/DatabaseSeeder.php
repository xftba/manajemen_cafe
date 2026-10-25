<?php

namespace Database\Seeders;

use App\Models\PengaturanCafe;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'nama' => 'Admin Cafe',
            'email' => 'admin@cafe.test',
            'password' => 'password',
            'role' => 'admin',
        ]);

        User::create([
            'nama' => 'Kasir Satu',
            'email' => 'kasir@cafe.test',
            'password' => 'password',
            'role' => 'kasir',
        ]);

        User::create([
            'nama' => 'Staff Gudang',
            'email' => 'gudang@cafe.test',
            'password' => 'password',
            'role' => 'staff_gudang',
        ]);

        PengaturanCafe::create([
            'nama_cafe' => 'Manajemen Cafe',
            'alamat' => 'Jakarta',
            'telepon' => '081234567890',
        ]);
    }
}