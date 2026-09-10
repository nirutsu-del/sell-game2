<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->string('facebook_url',2048)->nullable();
            $table->string('line_url',2048)->nullable();
            $table->string('discord_url',2048)->nullable();
            $table->string('opening_hours',300)->nullable();
            $table->boolean('floating_contact_enabled')->default(false);
            $table->text('announcement')->nullable();
            $table->boolean('announcement_enabled')->default(false);
        });
    }
    public function down(): void {
        Schema::table('store_settings', fn(Blueprint $table)=>$table->dropColumn([
            'facebook_url','line_url','discord_url','opening_hours',
            'floating_contact_enabled','announcement','announcement_enabled',
        ]));
    }
};
