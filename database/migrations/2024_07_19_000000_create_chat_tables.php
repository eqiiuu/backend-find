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
        // Chat Groups Table
        Schema::create('chat_groups', function (Blueprint $table) {
            $table->string('chat_group_id')->primary();
            $table->string('name');
            $table->integer('capacity')->default(10);
            $table->boolean('is_private')->default(false);
            $table->timestamps();
        });

        // Chat Group User Pivot Table
        Schema::create('chat_group_user', function (Blueprint $table) {
            $table->id();
            $table->string('chat_group_id');
            $table->string('user_id');
            $table->timestamps();

            $table->foreign('chat_group_id')
                ->references('chat_group_id')
                ->on('chat_groups')
                ->onDelete('cascade');
                
            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');
                
            $table->unique(['chat_group_id', 'user_id']);
        });

        // Messages Table
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('chat_group_id');
            $table->string('user_id');
            $table->text('message');
            $table->timestamps();

            $table->foreign('chat_group_id')
                ->references('chat_group_id')
                ->on('chat_groups')
                ->onDelete('cascade');
                
            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');
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