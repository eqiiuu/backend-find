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
        Schema::create('unread_messages', function (Blueprint $table) {
            $table->id();
            $table->string('chat_group_id');
            $table->string('user_id');
            $table->string('message_id');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->foreign('chat_group_id')
                  ->references('chat_group_id')
                  ->on('chat_groups')
                  ->onDelete('cascade');
                  
            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->onDelete('cascade');
                  
            $table->foreign('message_id')
                  ->references('message_id')
                  ->on('messages')
                  ->onDelete('cascade');

            // Add a unique constraint to prevent duplicate unread entries
            $table->unique(['chat_group_id', 'user_id', 'message_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('unread_messages');
    }
}; 