<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a message_type column to ticket_replies so inbound customer
     * emails (via Postmark webhook) can be distinguished from outgoing
     * agent/admin replies. Existing rows default to 'outgoing'.
     */
    public function up(): void
    {
        Schema::table('ticket_replies', function (Blueprint $table) {
            $table->enum('message_type', ['incoming', 'outgoing'])
                  ->default('outgoing')
                  ->after('body')
                  ->comment('incoming = customer email, outgoing = agent/admin reply');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_replies', function (Blueprint $table) {
            $table->dropColumn('message_type');
        });
    }
};
