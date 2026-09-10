<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->string('image')->nullable(); $table->timestamps();
        });
        Schema::create('game_accounts', function (Blueprint $table) {
            $table->id(); $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('title'); $table->text('description')->nullable(); $table->decimal('price', 14, 2);
            $table->longText('credentials_data'); // encrypted by model mutator
            $table->json('images')->nullable(); $table->enum('status', ['available', 'reserved', 'sold'])->default('available')->index();
            $table->timestamps(); $table->index(['category_id', 'status', 'price']);
        });
        Schema::create('gacha_boxes', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->text('description')->nullable(); $table->decimal('price_per_spin', 14, 2); $table->string('image')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('gacha_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('gacha_box_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_account_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('reward_type', ['game_account', 'credit'])->index(); $table->decimal('credit_amount', 14, 2)->nullable();
            $table->decimal('drop_rate', 7, 4); $table->timestamps();
        });
        Schema::create('topup_transactions', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->decimal('amount', 14, 2);
            $table->enum('payment_method', ['promptpay_slip', 'truemoney_gift'])->index(); $table->string('reference_no')->unique();
            $table->string('slip_path')->nullable(); $table->json('verification_payload')->nullable(); $table->enum('status', ['pending', 'success', 'failed'])->default('pending')->index(); $table->timestamps();
        });
        Schema::create('purchase_histories', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->foreignId('game_account_id')->constrained()->restrictOnDelete();
            $table->decimal('price_paid', 14, 2); $table->longText('account_data_delivered'); $table->enum('source', ['shop', 'gacha'])->default('shop'); $table->timestamps();
            $table->unique('game_account_id');
        });
        Schema::create('gacha_spins', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->foreignId('gacha_box_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gacha_item_id')->constrained()->restrictOnDelete(); $table->foreignId('purchase_history_id')->nullable()->constrained()->nullOnDelete(); $table->decimal('price_paid', 14, 2); $table->timestamps();
        });
        Schema::create('news', function (Blueprint $table) {
            $table->id(); $table->string('title'); $table->string('slug')->unique(); $table->longText('content'); $table->string('image')->nullable(); $table->boolean('is_published')->default(false)->index(); $table->timestamp('published_at')->nullable(); $table->timestamps();
        });
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->string('name'); $table->string('email'); $table->string('subject'); $table->text('message'); $table->timestamp('read_at')->nullable(); $table->timestamps();
        });
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->decimal('amount', 14, 2); $table->enum('type', ['credit', 'debit'])->index(); $table->string('description'); $table->morphs('reference'); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('wallet_transactions'); Schema::dropIfExists('contact_messages'); Schema::dropIfExists('news'); Schema::dropIfExists('gacha_spins'); Schema::dropIfExists('purchase_histories'); Schema::dropIfExists('topup_transactions'); Schema::dropIfExists('gacha_items'); Schema::dropIfExists('gacha_boxes'); Schema::dropIfExists('game_accounts'); Schema::dropIfExists('categories'); }
};
