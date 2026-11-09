<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // hasColumn: aman dijalankan walau sebagian kolom sudah ada
        Schema::table('transaksi', function (Blueprint $table) {
            if (! Schema::hasColumn('transaksi', 'tipe_pesanan')) {
                $table->string('tipe_pesanan', 20)->default('takeaway')->after('no_invoice');
            }
            if (! Schema::hasColumn('transaksi', 'nama_customer')) {
                $table->string('nama_customer', 100)->nullable()->after('tipe_pesanan');
            }
            if (! Schema::hasColumn('transaksi', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->default(0)->after('tanggal_transaksi');
            }
            if (! Schema::hasColumn('transaksi', 'pajak')) {
                $table->decimal('pajak', 12, 2)->default(0)->after('subtotal');
            }
        });

        Schema::table('detail_transaksi', function (Blueprint $table) {
            if (! Schema::hasColumn('detail_transaksi', 'catatan')) {
                $table->string('catatan', 150)->nullable()->after('subtotal');
            }
        });

        Schema::table('pembayaran', function (Blueprint $table) {
            if (! Schema::hasColumn('pembayaran', 'no_referensi')) {
                $table->string('no_referensi', 100)->nullable()->after('metode');
            }
        });

        Schema::table('pengaturan_cafe', function (Blueprint $table) {
            if (! Schema::hasColumn('pengaturan_cafe', 'qris_gambar')) {
                $table->string('qris_gambar')->nullable()->after('logo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropColumn(['tipe_pesanan', 'nama_customer', 'subtotal', 'pajak']);
        });
        Schema::table('detail_transaksi', function (Blueprint $table) {
            $table->dropColumn('catatan');
        });
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropColumn('no_referensi');
        });
        Schema::table('pengaturan_cafe', function (Blueprint $table) {
            $table->dropColumn('qris_gambar');
        });
    }
};