<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom ukuran dan kunci unik yang baru.
        //    Baris resep yang sudah ada otomatis menjadi resep Small.
        Schema::table('resep', function (Blueprint $table) {
            if (! Schema::hasColumn('resep', 'ukuran')) {
                $table->string('ukuran', 10)->default('small')->after('menu_id');
            }
        });

        $indeks = collect(Schema::getIndexes('resep'))->pluck('name');

        if (! $indeks->contains('resep_menu_ukuran_bahan_unique')) {
            Schema::table('resep', function (Blueprint $table) {
                $table->unique(['menu_id', 'ukuran', 'bahan_baku_id'], 'resep_menu_ukuran_bahan_unique');
            });
        }

        // 2. Hapus kunci unik lama (menu + bahan) setelah yang baru ada
        if ($indeks->contains('resep_menu_id_bahan_baku_id_unique')) {
            Schema::table('resep', function (Blueprint $table) {
                $table->dropUnique('resep_menu_id_bahan_baku_id_unique');
            });
        }

        // 3. Pengali dari pendekatan sebelumnya tidak dipakai lagi
        Schema::table('menu', function (Blueprint $table) {
            foreach (['pengali_medium', 'pengali_large'] as $kolom) {
                if (Schema::hasColumn('menu', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }

    public function down(): void
    {
        // Resep Medium dan Large dihapus supaya kunci unik lama bisa dipasang kembali
        DB::table('resep')->where('ukuran', '!=', 'small')->delete();

        Schema::table('resep', function (Blueprint $table) {
            $table->unique(['menu_id', 'bahan_baku_id']);
            $table->dropUnique('resep_menu_ukuran_bahan_unique');
            $table->dropColumn('ukuran');
        });
    }
};