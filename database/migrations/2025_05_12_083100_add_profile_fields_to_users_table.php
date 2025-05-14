<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfileFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'lokasi')) {
                $table->string('lokasi')->nullable();
            }
            if (!Schema::hasColumn('users', 'tentang')) {
                $table->text('tentang')->nullable();
            }
            if (!Schema::hasColumn('users', 'photo')) {
                $table->string('photo')->nullable();
            }
            if (!Schema::hasColumn('users', 'background')) {
                $table->string('background')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['lokasi', 'tentang', 'photo', 'background']);
        });
    }
};