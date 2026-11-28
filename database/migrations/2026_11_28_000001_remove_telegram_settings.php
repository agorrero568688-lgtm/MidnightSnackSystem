<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->whereIn('key', ['telegram_bot_token', 'telegram_chat_id'])->delete();
    }

    public function down(): void
    {
        // The old Telegram alerting settings are intentionally not restored.
    }
};
