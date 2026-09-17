<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\GameAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DemoAccountBatchSeeder extends Seeder
{
    public function run(): void
    {
        $themes = ['Aurora', 'Eclipse', 'Nova', 'Storm', 'Blaze', 'Crystal', 'Phantom', 'Solar', 'Lunar', 'Infinity'];
        $catalog = collect(config('demo_catalog'))->groupBy('game', preserveKeys: true);

        DB::transaction(function () use ($themes, $catalog) {
            foreach ($catalog as $game => $artworks) {
                $category = Category::where('name', $game)->firstOrFail();
                $slugs = $artworks->keys()->values();
                foreach ($themes as $index => $theme) {
                    $slug = $slugs[$index % $slugs->count()];
                    $template = $artworks[$slug];
                    $gameKey = ['Free Fire' => 'ff', 'Roblox' => 'roblox', 'Rov' => 'rov', 'Valorant' => 'valorant'][$game];
                    $image = 'demo-accounts/batch-new/'.$gameKey.'-'.strtolower($theme).'.webp';
                    if (!Storage::disk('public')->exists($image)) {
                        throw new \RuntimeException('Missing demo artwork: '.$image);
                    }
                    $number = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
                    GameAccount::firstOrCreate([
                        'category_id' => $category->id,
                        'title' => $game.' • '.$theme.' Collection #'.$number,
                    ], [
                        'price' => $template['price'] + ($index * 100),
                        'description' => "บัญชีตัวอย่างสำหรับสาธิตระบบ ไม่มีบัญชีเกมจริง\n\n"
                            .$template['rank'].' · '.$template['collection']."\n"
                            .$template['detail']."\nชุดคอลเลกชัน ".$theme.' หมายเลข '.$number
                            ."\n\nภาพประกอบเฉพาะรายการสร้างด้วย AI ข้อมูลแรงก์ สกิน และไอเทมเป็นข้อมูลสมมติ",
                        'images' => [$image],
                        'status' => 'available',
                        'credentials_data' => [
                            'username' => 'demo-batch-'.$category->id.'-'.$number.'@example.com',
                            'password' => 'DEMO-ONLY-NOT-A-REAL-ACCOUNT',
                        ],
                    ]);
                }
            }
        });
    }
}
