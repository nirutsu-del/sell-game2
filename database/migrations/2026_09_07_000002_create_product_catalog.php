<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('categories', function (Blueprint $t) {
            $t->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
            $t->boolean('is_featured')->default(false);
            $t->unsignedInteger('sort_order')->default(0);
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id(); $t->foreignId('category_id')->constrained()->restrictOnDelete();
            $t->string('name'); $t->text('description')->nullable(); $t->text('terms')->nullable();
            $t->string('image')->nullable(); $t->string('recipient_label')->default('Username / UID');
            $t->boolean('is_active')->default(true); $t->boolean('is_featured')->default(false); $t->timestamps();
        });
        Schema::create('product_variants', function (Blueprint $t) {
            $t->id(); $t->foreignId('product_id')->constrained()->restrictOnDelete(); $t->string('name');
            $t->decimal('price',14,2); $t->unsignedInteger('stock')->nullable();
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('service_orders', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $t->uuid('request_id'); $t->unique(['user_id','request_id']);
            $t->string('product_name'); $t->string('variant_name'); $t->unsignedInteger('quantity');
            $t->decimal('total',14,2); $t->text('recipient'); $t->text('terms')->nullable();
            $t->string('status')->default('pending'); $t->text('delivery_note')->nullable(); $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('service_orders'); Schema::dropIfExists('product_variants'); Schema::dropIfExists('products');
        Schema::table('categories', function (Blueprint $t) {
            $t->dropConstrainedForeignId('parent_id'); $t->dropColumn(['is_featured','sort_order']);
        });
    }
};
