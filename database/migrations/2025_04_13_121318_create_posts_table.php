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
        Schema::create('posts', function (Blueprint $table) {
            $table->string('post_id')->primary();
            $table->string('user_id'); // relasi ke users.user_id
            $table->string('community_id'); // relasi ke communities.community_id
            $table->date('post_date');
            $table->json('comments')->nullable(); // array of komentar
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
