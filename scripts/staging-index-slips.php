<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$app = require __DIR__.'/staging-bootstrap.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!app()->environment('staging') || config('database.default') !== 'sqlite') throw new RuntimeException('Staging only');
$status = Illuminate\Support\Facades\Artisan::call('store:index-slips');
echo Illuminate\Support\Facades\Artisan::output();
exit($status);
