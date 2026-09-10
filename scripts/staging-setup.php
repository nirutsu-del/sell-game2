<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../vendor/autoload.php';
use Illuminate\Support\Facades\{Artisan, File};
use App\Models\{User, Category, GameAccount, StoreSetting, GachaBox, GachaItem};

$root = dirname(__DIR__).'/storage/app/local-staging';
if (is_file($root.'/database.sqlite')) { fwrite(STDERR, "Staging already exists; nothing overwritten.\n"); exit(1); }
foreach (['cache','public','storage/app/private','storage/app/public','storage/framework/cache/data','storage/framework/sessions','storage/framework/views','storage/logs'] as $dir) {
    if (!is_dir($root.'/'.$dir)) mkdir($root.'/'.$dir, 0700, true);
}
file_put_contents($root.'/key', 'base64:'.base64_encode(random_bytes(32)));
touch($root.'/database.sqlite');
$app = require __DIR__.'/staging-bootstrap.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (Artisan::call('migrate', ['--force'=>true]) !== 0) throw new RuntimeException('Staging migration failed');
File::copyDirectory(dirname(__DIR__).'/public/build', $root.'/public/build');
$password = bin2hex(random_bytes(10));
User::create(['name'=>'Staging admin', 'email'=>'admin@staging.test', 'password'=>$password, 'role'=>'admin', 'balance'=>0]);
User::create(['name'=>'Staging buyer', 'email'=>'buyer@staging.test', 'password'=>$password, 'role'=>'user', 'balance'=>0]);
file_put_contents($root.'/credentials.json', json_encode(['admin'=>'admin@staging.test','buyer'=>'buyer@staging.test','password'=>$password], JSON_PRETTY_PRINT));
foreach (['promptpay'=>'#7c3aed', 'truemoney'=>'#ea580c', 'product'=>'#0f766e'] as $name=>$color) {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="480" height="480" viewBox="0 0 480 480"><rect width="480" height="480" fill="'.$color.'"/><rect x="24" y="24" width="432" height="432" rx="24" fill="white"/><text x="240" y="185" text-anchor="middle" font-family="sans-serif" font-size="30" fill="'.$color.'">'.strtoupper($name).'</text><text x="240" y="250" text-anchor="middle" font-family="sans-serif" font-size="38">TEST ONLY</text><text x="240" y="300" text-anchor="middle" font-family="sans-serif" font-size="18">NOT A PAYMENT QR</text></svg>';
    file_put_contents($root.'/storage/app/public/'.$name.'.svg', $svg);
}
StoreSetting::create(['name'=>'Mizuki STAGING', 'description'=>'ระบบทดสอบ ไม่ใช้เงินจริง',
    'promptpay_qr'=>'promptpay.svg', 'truemoney_qr'=>'truemoney.svg',
    'announcement_enabled'=>true, 'announcement'=>'STAGING — ข้อมูลจำลอง ห้ามชำระเงินจริง',
    'promptpay_instructions'=>'รูปจำลองสำหรับทดสอบการแสดงผล ไม่ใช่ QR รับเงินจริง',
    'truemoney_instructions'=>'รูปจำลองสำหรับทดสอบการแสดงผล ไม่ใช่ QR รับเงินจริง']);
$category = Category::create(['name'=>'เกมทดสอบ', 'slug'=>'staging-game', 'image'=>'product.svg', 'is_featured'=>true]);
foreach (range(1,3) as $n) GameAccount::create(['category_id'=>$category->id, 'title'=>'ไอดีทดสอบ '.$n,
    'price'=>199, 'status'=>'available', 'images'=>['product.svg'],
    'credentials_data'=>['username'=>'dummy-player-'.$n, 'password'=>'dummy-password-not-real']]);
$box = GachaBox::create(['name'=>'กล่องทดสอบ', 'description'=>'เครดิตจำลองเท่านั้น', 'price_per_spin'=>30, 'is_active'=>true, 'image'=>'product.svg']);
GachaItem::create(['gacha_box_id'=>$box->id, 'reward_type'=>'credit', 'credit_amount'=>5, 'drop_rate'=>100]);
echo "Staging ready. Credentials: storage/app/local-staging/credentials.json\n";
