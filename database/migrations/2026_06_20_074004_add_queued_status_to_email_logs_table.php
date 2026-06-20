<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add 'queued' and 'sent' to the email_logs status ENUM.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE email_logs MODIFY COLUMN status ENUM('processed', 'duplicate', 'failed', 'queued', 'sent') DEFAULT 'processed'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE email_logs MODIFY COLUMN status ENUM('processed', 'duplicate', 'failed') DEFAULT 'processed'");
    }
};
