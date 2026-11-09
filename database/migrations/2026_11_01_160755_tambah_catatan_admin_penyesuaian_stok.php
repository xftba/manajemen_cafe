<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penyesuaian_stok', function (Blueprint $table) {
            if (! Schema::hasColumn('penyesuaian_stok', 'catatan_admin')) {
                $table->string('catatan_admin')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penyesuaian_stok', function (Blueprint $table) {
            $table->dropColumn('catatan_admin');
        });
    }
};