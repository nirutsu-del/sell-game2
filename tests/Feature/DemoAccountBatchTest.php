<?php

namespace Tests\Feature;

use App\Models\{Category, GameAccount};
use Database\Seeders\DemoAccountBatchSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DemoAccountBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_batch_adds_ten_per_game_with_images_without_resetting_sold_accounts(): void
    {
        Storage::fake('public');
        foreach (config('demo_catalog') as $slug => $item) {
            Category::firstOrCreate(['name' => $item['game']], ['slug' => Str::slug($item['game'])]);
            $gameKey = ['Free Fire' => 'ff', 'Roblox' => 'roblox', 'Rov' => 'rov', 'Valorant' => 'valorant'][$item['game']];
            foreach (['aurora', 'eclipse', 'nova', 'storm', 'blaze', 'crystal', 'phantom', 'solar', 'lunar', 'infinity'] as $theme) {
                Storage::disk('public')->put('demo-accounts/batch-new/'.$gameKey.'-'.$theme.'.webp', 'demo');
            }
        }
        $this->seed(DemoAccountBatchSeeder::class);
        $this->assertDatabaseCount('game_accounts', 40);
        foreach (Category::withCount('gameAccounts')->get() as $category) {
            $this->assertSame(10, $category->game_accounts_count);
        }
        foreach (GameAccount::all() as $account) {
            Storage::disk('public')->assertExists($account->images[0]);
            $this->assertStringContainsString('ไม่มีบัญชีเกมจริง', $account->description);
        }
        $account = GameAccount::firstOrFail();
        $account->update(['status' => 'sold']);
        $this->seed(DemoAccountBatchSeeder::class);
        $this->assertDatabaseCount('game_accounts', 40);
        $this->assertSame('sold', $account->fresh()->status);
    }
}
