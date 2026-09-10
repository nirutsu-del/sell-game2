<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('topup_transactions', function (Blueprint $table) {
            $table->uuid('request_id')->nullable();
            $table->string('request_fingerprint', 64)->nullable();
            $table->unique(['user_id', 'request_id'], 'topups_user_request_unique');
        });
    }
    public function down(): void
    {
        Schema::table('topup_transactions', function (Blueprint $table) {
            $table->dropUnique('topups_user_request_unique');
            $table->dropColumn(['request_id', 'request_fingerprint']);
        });
    }
};
