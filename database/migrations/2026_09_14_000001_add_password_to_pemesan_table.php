<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pemesan', function (Blueprint $table) {
            // Pemesan lama (dibuat via checkout guest, updateOrCreate by email) belum punya
            // kata sandi — nullable supaya baris existing tetap valid sampai pemiliknya
            // mendaftar/klaim akun lewat form registrasi Pemesan.
            $table->string('password')->nullable()->after('email');
            $table->rememberToken()->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pemesan', function (Blueprint $table) {
            $table->dropColumn(['password', 'remember_token']);
        });
    }
};
