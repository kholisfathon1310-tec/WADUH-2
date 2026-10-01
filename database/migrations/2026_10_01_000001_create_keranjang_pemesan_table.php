<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Salinan keranjang reservasi milik pemesan yang login — supaya keranjang yang belum
 * diajukan tetap ada setelah pemesan keluar lalu masuk kembali. Satu baris per pemesan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keranjang_pemesan', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->unsignedInteger('id_pemesan')->primary();
            $table->longText('item');
            $table->longText('dokumen')->nullable();
            $table->timestamps();

            $table->foreign('id_pemesan')->references('id_pemesan')->on('pemesan')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keranjang_pemesan');
    }
};
