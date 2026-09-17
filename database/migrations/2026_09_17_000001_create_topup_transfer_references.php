<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('topup_transfer_references', function (Blueprint $table) {
            $table->string('identity_hash', 64)->primary();
            // Retain the reservation even if an old user/topup is removed.
            $table->unsignedBigInteger('topup_id')->index();
        });

        DB::table('topup_transactions')->where('status', 'success')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                $payload = json_decode($row->verification_payload ?? '{}', true);
                $reference = $payload['transfer_reference'] ?? null;
                if (!is_string($reference) || trim($reference) === '') continue;
                // Existing duplicates retain their history; reserve the first occurrence.
                DB::table('topup_transfer_references')->insertOrIgnore([
                    'identity_hash'=>hash('sha256', $row->payment_method."\0".mb_strtoupper(trim($reference), 'UTF-8')),
                    'topup_id'=>$row->id,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topup_transfer_references');
    }
};
