<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Storage};
use App\Models\TopupTransaction;
class IndexTopupSlips extends Command {
    protected $signature = 'store:index-slips';
    protected $description = 'Index existing private slip files without changing topup statuses';
    public function handle(): int {
        $missing = $duplicates = $indexed = 0;
        foreach (TopupTransaction::whereNotNull('slip_path')->orderBy('id')->cursor() as $topup) {
            if (!Storage::disk('local')->exists($topup->slip_path)) { $missing++; continue; }
            $hash = hash_file('sha256', Storage::disk('local')->path($topup->slip_path));
            $added = DB::table('topup_slip_hashes')->insertOrIgnore(['hash'=>$hash,'topup_id'=>$topup->id]);
            if ($added) $indexed++;
            elseif ((int) DB::table('topup_slip_hashes')->where('hash',$hash)->value('topup_id') !== $topup->id) $duplicates++;
        }
        $this->line("Indexed: $indexed; missing files: $missing; historical duplicate files: $duplicates");
        if ($duplicates || $missing) $this->warn('Review historical records manually. No records were deleted or approved.');
        return ($missing || $duplicates) ? self::FAILURE : self::SUCCESS;
    }
}
