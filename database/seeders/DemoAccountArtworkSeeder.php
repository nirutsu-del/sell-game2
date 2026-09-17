<?php

namespace Database\Seeders;

use App\Models\GameAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DemoAccountArtworkSeeder extends Seeder
{
    public function run(): void
    {
        $manifest = json_decode(file_get_contents(base_path('docs/generated-account-artwork.json')), true, flags: JSON_THROW_ON_ERROR);
        $games = ['ff' => 'Free Fire', 'roblox' => 'Roblox', 'rov' => 'Rov', 'valorant' => 'Valorant'];
        $themes = ['Aurora', 'Eclipse', 'Nova', 'Storm', 'Blaze', 'Crystal', 'Phantom', 'Solar', 'Lunar', 'Infinity'];

        DB::transaction(function () use ($manifest, $games, $themes) {
            foreach ($manifest['completed'] as $artwork) {
                $game = $games[$artwork['key']];
                $number = str_pad((string) (array_search($artwork['theme'], $themes, true) + 1), 2, '0', STR_PAD_LEFT);
                $image = 'demo-accounts/batch-new/'.$artwork['key'].'-'.strtolower($artwork['theme']).'.webp';
                if (!Storage::disk('public')->exists($image)) {
                    throw new \RuntimeException('Missing generated artwork: '.$image);
                }
                $account = GameAccount::whereHas('category', fn ($query) => $query->where('name', $game))
                    ->where('title', $game.' • '.$artwork['theme'].' Collection #'.$number)->firstOrFail();
                $account->update([
                    'images' => [$image],
                    'description' => str_replace('ภาพประกอบสร้างด้วย AI และใช้ร่วมกับรายการตัวอย่างในเกมเดียวกัน', 'ภาพประกอบเฉพาะรายการสร้างด้วย AI', $account->description),
                ]);
            }
        });
    }
}
