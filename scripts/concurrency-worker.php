<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../vendor/autoload.php';
$dir = realpath($argv[1] ?? '');
$base = realpath(dirname(__DIR__).'/storage/app/concurrency-tests');
if (!$dir || !$base || dirname($dir) !== $base) throw new RuntimeException('Invalid isolated run');
$job = json_decode(file_get_contents($dir.'/worker-config.json'),true,512,JSON_THROW_ON_ERROR);
if (!preg_match('/^mizuki_race_[a-f0-9]{12}$/',$job['database'])) throw new RuntimeException('Invalid test database');
$slot = (int)($argv[2] ?? -1);
$task = $job['tasks'][$slot] ?? throw new RuntimeException('Missing task');
foreach (['APP_ENV'=>'testing','APP_KEY'=>$job['key'],'APP_CONFIG_CACHE'=>$dir.'/config-unused.php',
    'APP_ROUTES_CACHE'=>$dir.'/routes-unused.php','DB_URL'=>'','DB_CONNECTION'=>'mysql',
    'DB_HOST'=>$job['host'],'DB_PORT'=>(string)$job['port'],'DB_DATABASE'=>$job['database'],
    'DB_USERNAME'=>$job['username'],'DB_PASSWORD'=>$job['password'],'SESSION_DRIVER'=>'array',
    'CACHE_STORE'=>'array','MAIL_MAILER'=>'array','QUEUE_CONNECTION'=>'sync'] as $key=>$value) {
    $_ENV[$key] = $_SERVER[$key] = $value; putenv($key.'='.$value);
}
$app = require __DIR__.'/../bootstrap/app.php';
$app->loadEnvironmentFrom('.env.race-not-used');
$app->useStoragePath($dir.'/storage');
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$request = Illuminate\Http\Request::create('http://localhost/test','POST',$task['body'] ?? []);
$request->setLaravelSession(new Illuminate\Session\Store('race',new Illuminate\Session\ArraySessionHandler(120)));
$app->instance('request',$request);
$authenticatedUser = App\Models\User::findOrFail($task['user']);
// Match the HTTP auth guard: user() returns the already-authenticated model,
// rather than opening a new consistent-read snapshot inside each transaction.
$request->setUserResolver(fn()=>$authenticatedUser);
// Connect and load models before signaling ready, so PHP startup is outside the race.
$user = $request->user();
file_put_contents($dir.'/ready-'.$slot,'ready');
$deadline = microtime(true)+20;
while (!is_file($dir.'/release')) {
    if (microtime(true)>$deadline) exit(2);
    usleep(10000);
}
$start = microtime(true);
try {
    if ($task['kind'] === 'buy') {
        $result = app(App\Services\StoreService::class)->buy($user,$task['target']);
        $id = $result->id;
    } elseif ($task['kind'] === 'spin_retry') {
        $result = app(App\Services\StoreService::class)->spin($user,$task['target'],$task['body']['request_id']);
        $id = $result['spin_id'];
    } elseif (in_array($task['kind'], ['approve','same_reference'], true)) {
        app(App\Http\Controllers\Admin\TopupController::class)->approve($request,App\Models\TopupTransaction::findOrFail($task['target']));
        $id = $task['target'];
    } else {
        $request->files->set('slip',new Illuminate\Http\UploadedFile($dir.'/slip.png','slip.png','image/png',null,true));
        app(App\Http\Controllers\TopupController::class)->store($request);
        $id = App\Models\TopupTransaction::where('user_id',$user->id)->where('request_id',$task['body']['request_id'])->value('id');
    }
    $output = ['outcome'=>'ok','id'=>$id];
} catch (Illuminate\Validation\ValidationException $error) {
    $output = ['outcome'=>'validation','fields'=>array_keys($error->errors())];
} catch (Throwable $error) {
    $output = ['outcome'=>'error','type'=>get_class($error),'code'=>(string)$error->getCode(),
        'message'=>str_replace([$job['password'],$job['key']], '[redacted]', $error->getMessage()),
        'location'=>basename($error->getFile()).':'.$error->getLine()];
}
$output['started_at'] = $start;
$output['finished_at'] = microtime(true);
echo json_encode($output,JSON_THROW_ON_ERROR).PHP_EOL;
exit($output['outcome'] === 'error' ? 1 : 0);
