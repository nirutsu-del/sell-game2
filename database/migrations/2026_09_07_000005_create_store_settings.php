<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('store_settings', function (Blueprint $t) {
            $t->id(); $t->string('name',100); $t->text('description')->nullable();
            $t->string('logo')->nullable(); $t->string('promptpay_qr')->nullable(); $t->string('truemoney_qr')->nullable();
            $t->text('promptpay_instructions')->nullable(); $t->text('truemoney_instructions')->nullable();
            $t->timestamps();
        });
        Schema::create('store_banners', function (Blueprint $t) {
            $t->id(); $t->string('title',150); $t->string('image'); $t->string('link',2048)->nullable();
            $t->unsignedInteger('sort_order')->default(0); $t->boolean('is_active')->default(true); $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('store_banners'); Schema::dropIfExists('store_settings'); }
};
