<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu', function (Blueprint $table) {
            if (! Schema::hasColumn('menu', 'deskripsi')) {
                $table->string('deskripsi', 200)->nullable()->after('nama');
            }
            if (! Schema::hasColumn('menu', 'pakai_varian')) {
                $table->boolean('pakai_varian')->default(true)->after('is_active');
            }
            if (! Schema::hasColumn('menu', 'tambahan_medium')) {
                $table->decimal('tambahan_medium', 12, 2)->default(0)->after('harga');
            }
            if (! Schema::hasColumn('menu', 'tambahan_large')) {
                $table->decimal('tambahan_large', 12, 2)->default(0)->after('tambahan_medium');
            }
        });

        Schema::table('detail_transaksi', function (Blueprint $table) {
            if (! Schema::hasColumn('detail_transaksi', 'temperatur')) {
                $table->string('temperatur', 10)->nullable()->after('subtotal');
            }
            if (! Schema::hasColumn('detail_transaksi', 'ukuran')) {
                $table->string('ukuran', 10)->nullable()->after('temperatur');
            }
            if (! Schema::hasColumn('detail_transaksi', 'gula')) {
                $table->string('gula', 10)->nullable()->after('ukuran');
            }
        });
    }

    public function down(): void
    {
        Schema::table('menu', function (Blueprint $table) {
            $table->dropColumn(['deskripsi', 'pakai_varian', 'tambahan_medium', 'tambahan_large']);
        });
        Schema::table('detail_transaksi', function (Blueprint $table) {
            $table->dropColumn(['temperatur', 'ukuran', 'gula']);
        });
    }
};