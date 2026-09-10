<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('order_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name');
            $table->string('from_status');
            $table->string('to_status');
            $table->text('note')->nullable();
            $table->timestamp('created_at');
        });
        Schema::table('service_orders', function (Blueprint $table) {
            $table->index(['status','created_at']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('order_status_events');
        Schema::table('service_orders', fn (Blueprint $table) => $table->dropIndex(['status','created_at']));
    }
};
