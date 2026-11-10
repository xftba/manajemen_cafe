<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resep', function (Blueprint $table) {
            $table->boolean('ikut_gula')->default(false)->after('temperatur');
        });
    }

    public function down(): void
    {
        Schema::table('resep', function (Blueprint $table) {
            $table->dropColumn('ikut_gula');
        });
    }
};