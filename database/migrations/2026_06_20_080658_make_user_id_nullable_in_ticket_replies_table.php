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
        Schema::table('ticket_replies', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['user_id']);
        });

        Schema::table('ticket_replies', function (Blueprint $table) {
            // Change column to nullable and add foreign key back with cascade onDelete
            $table->foreignId('user_id')->nullable()->change()->constrained('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_replies', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('ticket_replies', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change()->constrained('users')->onDelete('cascade');
        });
    }
};
