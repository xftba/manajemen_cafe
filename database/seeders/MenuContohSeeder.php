<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuContohSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Coffee' => [
                ['Latte', 12000], ['Spanish Latte', 15000], ['Caramel Macchiato', 16000],
                ['Cappuccino', 14000], ['Americano', 10000], ['Flat White', 15000],
                ['Cafe Mocha', 16000], ['Butterscotch', 16000], ['Hazelnut', 16000],
            ],
            'Non-Coffee' => [['Coklat', 14000], ['Matcha Latte', 16000], ['Red Velvet', 16000]],
            'Tea'        => [['Es Teh', 6000], ['Lemon Tea', 9000], ['Teh Tarik', 10000]],
            'Snack'      => [['Kentang Goreng', 12000], ['Croissant', 15000], ['Roti Bakar', 13000]],
        ];

        foreach ($data as $namaKategori => $items) {
            $kategori = Kategori::firstOrCreate(['nama' => $namaKategori]);

            foreach ($items as [$nama, $harga]) {
                Menu::firstOrCreate(
                    ['nama' => $nama],
                    ['kategori_id' => $kategori->id, 'harga' => $harga, 'is_active' => true]
                );
            }
        }

        // contoh menu yang habis
        Menu::where('nama', 'Hazelnut')->update(['is_active' => false]);
    }
}