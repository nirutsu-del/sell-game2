<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\GameAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (config('demo_catalog') as $slug => $item) {
                $image = 'demo-accounts/'.$slug.'.webp';
                if (!Storage::disk('public')->exists($image)) {
                    throw new \RuntimeException('Missing demo artwork: '.$image);
                }
                $category = Category::where('name', $item['game'])->firstOrFail();
                GameAccount::firstOrCreate(['title'=>$item['title']], [
                    'category_id'=>$category->id,
                    'price'=>$item['price'],
                    'description'=>"สินค้าจำลองสำหรับมินิโปรเจกต์ ไม่มีบัญชีเกมจริง\n\n".$item['rank'].' · '.$item['collection']."\n".$item['detail']."\n\nภาพเป็นภาพประกอบที่สร้างด้วย AI ข้อมูลแรงก์ สกิน และไอเทมเป็นข้อมูลสมมติสำหรับสาธิต",
                    'images'=>[$image],
                    'status'=>'available',
                    'credentials_data'=>['username'=>'demo-'.$slug.'@example.com','password'=>'DEMO-ONLY-NOT-A-REAL-ACCOUNT'],
                ]);
            }
        });
    }
}
