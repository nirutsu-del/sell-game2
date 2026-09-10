<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('topup_slip_hashes', function (Blueprint $table) {
            $table->string('hash', 64)->primary();
            $table->foreignId('topup_id')->nullable()->constrained('topup_transactions')->restrictOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('topup_slip_hashes'); }
};
