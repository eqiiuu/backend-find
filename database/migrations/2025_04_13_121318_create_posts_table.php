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
            $table->unsignedBigInteger('user_id'); // relasi ke users.user_id
            $table->string('title');
            $table->string('community_id'); // relasi ke communities.community_id
            $table->date('post_date');
            $table->string('image');
            $table->string('description');
            $table->json('comments'); // array of komentar
            $table->timestamps();
        });
    }

    protected $attributes = [
        'comments' => '[]',
    ];

    protected $casts = [
        'comments' => 'array',  
        'post_date' => 'datetime'
    ];

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
