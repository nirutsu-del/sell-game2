<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('waiting_on')->default('staff')->index();
            $table->string('category')->default('general');
            $table->string('order_reference')->nullable();
        });
        DB::table('contact_messages')->orderBy('id')->chunkById(200, function ($messages) {
            foreach ($messages as $message) {
                $last = DB::table('contact_replies')->where('contact_message_id',$message->id)->orderByDesc('id')->first();
                if ($last && $last->from_staff) DB::table('contact_messages')->where('id',$message->id)->update(['waiting_on'=>'customer']);
            }
        });
        Schema::create('contact_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_reply_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('mime');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_attachments');
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropIndex(['waiting_on']);
            $table->dropColumn(['waiting_on','category','order_reference']);
        });
    }
};
