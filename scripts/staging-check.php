<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$app = require __DIR__.'/staging-bootstrap.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$root = dirname(__DIR__).'/storage/app/local-staging';
$checks = [
    'staging_environment' => app()->environment('staging'),
    'isolated_sqlite' => config('database.default') === 'sqlite' && realpath(config('database.connections.sqlite.database')) === realpath($root.'/database.sqlite'),
    'isolated_storage' => realpath(storage_path()) === realpath($root.'/storage'),
    'isolated_public' => realpath(public_path()) === realpath($root.'/public'),
    'isolated_cookie' => config('session.cookie') === 'mizuki_local_staging',
    'mail_log_only' => config('mail.default') === 'log',
];
if (in_array('--sync-assets', $argv, true)) {
    if (!Illuminate\Support\Facades\File::copyDirectory(dirname(__DIR__).'/public/build', $root.'/public/build')) throw new RuntimeException('Asset copy failed');
}
echo json_encode(['checks'=>$checks,'users'=>App\Models\User::count(),'topups'=>App\Models\TopupTransaction::count(),
    'purchases'=>App\Models\PurchaseHistory::count(),'spins'=>App\Models\GachaSpin::count()], JSON_PRETTY_PRINT).PHP_EOL;
exit(in_array(false, $checks, true) ? 1 : 0);
