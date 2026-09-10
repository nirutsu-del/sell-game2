<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('resolved_by_name')->nullable();
            $table->text('resolution_note')->nullable();
            $table->index(['resolved_at','read_at']);
        });
    }
    public function down(): void {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropIndex(['resolved_at','read_at']);
            $table->dropConstrainedForeignId('resolved_by');
            $table->dropColumn(['resolved_at','resolved_by_name','resolution_note']);
        });
    }
};
