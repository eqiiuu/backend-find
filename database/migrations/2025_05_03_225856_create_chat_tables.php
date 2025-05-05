<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('chat_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('capacity');
            $table->timestamps();
        });
        
        Schema::create('chat_group_user', function (Blueprint $table) {
            $table->foreignId('chat_group_id')->constrained();
            $table->string('user_id');
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->timestamps();
        });
        
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_group_id')->constrained();
            $table->string('user_id');
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->text('message');
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('chat_group_user');
        Schema::dropIfExists('chat_groups');
    }
};
