<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('topup_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topup_id')->unique()->constrained('topup_transactions')->restrictOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('admin_name');
            $table->string('reference_no');
            $table->decimal('amount',14,2);
            $table->string('action',20)->index();
            $table->string('from_status',20);
            $table->string('to_status',20);
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->index();
        });
    }
    public function down(): void { Schema::dropIfExists('topup_reviews'); }
};
