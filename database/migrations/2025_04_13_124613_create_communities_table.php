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
        Schema::create('communities', function (Blueprint $table) {
            $table->string('community_id')->primary();
            $table->string('owner_id'); // relasi ke users.user_id
            $table->string('gambar')->nullable(); // path ke gambar
            $table->float('koordinat');
            $table->text('description');
            $table->json('anggota')->nullable(); // array of user_id
            $table->integer('capacity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communities');
    }
};
