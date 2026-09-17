<?php

// Isolated browser fixtures: no application database or real customer records are used.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config([
    'database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
    'database.connections.sqlite.url' => null, 'session.driver' => 'array',
    'cache.default' => 'array', 'app.url' => 'http://127.0.0.1:8187',
]);
$app->instance('env', 'local');
Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1:8187');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
$user = App\Models\User::factory()->create(['balance' => 1000]);
$box = App\Models\GachaBox::create(['name' => 'Browser review fixture', 'price_per_spin' => 10, 'is_active' => true]);
App\Models\GachaItem::create(['gacha_box_id' => $box->id, 'reward_type' => 'credit', 'credit_amount' => 25, 'drop_rate' => 100]);
Illuminate\Support\Facades\Auth::login($user);
$directory = storage_path('app/private/review-browser');
if (!is_dir($directory)) mkdir($directory, 0777, true);
$hotFile = $directory.'/hot';
Illuminate\Support\Facades\Vite::useHotFile($hotFile);
if (isset($argv[1])) file_put_contents($hotFile, $argv[1]);
try {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    foreach (['gacha' => '/gacha/'.$box->id, 'wallet' => '/wallet'] as $name => $path) {
        $request = Illuminate\Http\Request::create('http://127.0.0.1:8187'.$path);
        $response = $kernel->handle($request);
        if ($response->getStatusCode() !== 200) throw new RuntimeException('Fixture failed: '.$name.' '.$response->getStatusCode());
        file_put_contents($directory.'/'.$name.'.json', json_encode([
            'html' => $response->getContent(), 'csp' => $response->headers->get('Content-Security-Policy'),
        ], JSON_THROW_ON_ERROR));
        $kernel->terminate($request, $response);
    }
} finally {
    if (is_file($hotFile)) unlink($hotFile);
}
echo "Rendered isolated Wallet and Gacha fixtures.\n";
