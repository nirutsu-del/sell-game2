<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\{DB, Artisan, File};
use App\Models\{User, Category, GameAccount, TopupTransaction, PurchaseHistory, WalletTransaction, TopupReview};
use Symfony\Component\Process\Process;

$id = bin2hex(random_bytes(6));
$schema = 'mizuki_race_'.$id;
$username = 'race_'.$id;
$password = bin2hex(random_bytes(24));
$base = storage_path('app/concurrency-tests');
$dir = $base.'/'.$id;
File::ensureDirectoryExists($dir,0700);
$adminPdo = null;
$createdDb = $createdUser = false;
$processes = [];
$report = ['run'=>$id,'tested_at'=>date(DATE_ATOM),'workers_per_case'=>4,'rounds'=>2,'cases'=>[]];
$exit = 1;
try {
    $source = DB::connection()->getConfig();
    if (!in_array($source['driver'],['mysql','mariadb'],true) || !in_array($source['host'],['127.0.0.1','localhost'],true)) throw new RuntimeException('Local MySQL required');
    $adminPdo = DB::connection()->getPdo();
    $report['database_engine'] = $adminPdo->query('SELECT VERSION()')->fetchColumn();
    $adminPdo->exec("CREATE DATABASE `$schema` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"); $createdDb = true;
    $adminPdo->exec("CREATE USER '$username'@'127.0.0.1' IDENTIFIED BY ".$adminPdo->quote($password)); $createdUser = true;
    $adminPdo->exec("GRANT ALL PRIVILEGES ON `$schema`.* TO '$username'@'127.0.0.1'");
    $config = array_merge($source,['url'=>null,'host'=>'127.0.0.1','database'=>$schema,'username'=>$username,'password'=>$password]);
    unset($config['name']);
    // Never retain a production connection reachable by migrations or application models.
    foreach (array_keys(config('database.connections')) as $connectionName) {
        DB::purge($connectionName);
        config(['database.connections.'.$connectionName=>$config]);
    }
    config(['database.connections.race'=>$config,'database.default'=>'race','mail.default'=>'array','cache.default'=>'array','queue.default'=>'sync']);
    \Illuminate\Support\Facades\Schema::clearResolvedInstance('db.schema');
    $app->forgetInstance('db.schema');
    if (DB::connection()->getDatabaseName() !== $schema) throw new RuntimeException('Isolation check failed');
    if (Artisan::call('migrate',['--database'=>'race','--force'=>true]) !== 0) throw new RuntimeException('Isolated migration failed');
    $key = 'base64:'.base64_encode(random_bytes(32));
    config(['app.key'=>$key]); $app->forgetInstance('encrypter');
    // No production rows or uploads are copied.
    $admin = User::create(['name'=>'Race admin','email'=>'race-admin@example.test','password'=>bin2hex(random_bytes(16)),'role'=>'admin']);
    $category = Category::create(['name'=>'Race category','slug'=>'race-category']);
    $passed = true;
    for ($round=1;$round<=2;$round++) {
        foreach (['buy','approve','retry','same_slip'] as $kind) {
            $tasks = []; $users = [];
            foreach (range(0,3) as $n) $users[] = User::create(['name'=>'Race user','email'=>"$round-$kind-$n@example.test",'password'=>bin2hex(random_bytes(16)),'balance'=>$kind==='buy'?300:0]);
            $target = null;
            if ($kind==='buy') $target = GameAccount::create(['category_id'=>$category->id,'title'=>'Race account','price'=>199,'status'=>'available','credentials_data'=>['username'=>'test-only']])->id;
            if ($kind==='approve') $target = TopupTransaction::create(['user_id'=>$users[0]->id,'amount'=>150,'payment_method'=>'promptpay_slip','reference_no'=>"RACE-$round"])->id;
            $requestId = (string) Illuminate\Support\Str::uuid();
            foreach (range(0,3) as $n) {
                $body = $kind==='approve' ? ['funds_received'=>1,'received_amount'=>150,'transfer_reference'=>'RACE-ONLY'] : ['request_id'=>$kind==='retry'?$requestId:(string)Illuminate\Support\Str::uuid(),'amount'=>100,'payment_method'=>'promptpay_slip'];
                $tasks[] = ['kind'=>$kind,'user'=>$kind==='approve'?$admin->id:($kind==='retry'?$users[0]->id:$users[$n]->id),'target'=>$target,'body'=>$body];
            }
            foreach (range(0,3) as $n) if (is_file($dir.'/ready-'.$n)) unlink($dir.'/ready-'.$n);
            if (is_file($dir.'/release')) unlink($dir.'/release');
            file_put_contents($dir.'/slip.png',base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=')."$round-$kind");
            file_put_contents($dir.'/worker-config.json',json_encode(['database'=>$schema,'host'=>'127.0.0.1','port'=>$config['port'],'username'=>$username,'password'=>$password,'key'=>$key,'tasks'=>$tasks],JSON_THROW_ON_ERROR));
            $processes = [];
            foreach (range(0,3) as $n) {
                $p = new Process([PHP_BINARY,__DIR__.'/concurrency-worker.php',$dir,(string)$n],dirname(__DIR__),null,null,30);
                $p->start(); $processes[] = $p;
            }
            $deadline = microtime(true)+15;
            while (count(glob($dir.'/ready-*'))<4) {
                if (microtime(true)>$deadline) throw new RuntimeException('Worker startup timeout');
                usleep(20000);
            }
            // Hold the contested row until all workers have been released into the operation.
            $gate = new PDO('mysql:host=127.0.0.1;port='.$config['port'].';dbname='.$schema,$username,$password,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $gate->beginTransaction();
            if ($kind==='buy') $gate->query("SELECT id FROM game_accounts WHERE id=$target FOR UPDATE")->fetchAll();
            elseif ($kind==='approve') $gate->query("SELECT id FROM topup_transactions WHERE id=$target FOR UPDATE")->fetchAll();
            else foreach ($users as $u) $gate->query('SELECT id FROM users WHERE id='.$u->id.' FOR UPDATE')->fetchAll();
            file_put_contents($dir.'/release','go'); usleep(300000); $gate->commit(); $gate = null;
            $outputs = [];
            foreach ($processes as $p) { $p->wait(); $outputs[] = json_decode(trim($p->getOutput()),true,512,JSON_THROW_ON_ERROR); }
            $ok = count(array_filter($outputs,fn($o)=>$o['outcome']==='ok'));
            $validation = count(array_filter($outputs,fn($o)=>$o['outcome']==='validation'));
            $ids = array_map(fn($u)=>$u->id,$users);
            $balances = User::whereIn('id',$ids)->orderBy('id')->pluck('balance')->all();
            $debits = WalletTransaction::whereIn('user_id',$ids)->where('type','debit')->count();
            $credits = WalletTransaction::whereIn('user_id',$ids)->where('type','credit')->count();
            $topups = TopupTransaction::whereIn('user_id',$ids)->count();
            $casePass = match($kind) {
                'buy' => $ok===1 && $validation===3 && $debits===1 && $credits===0 && (float)array_sum($balances)===1001.0 && PurchaseHistory::where('game_account_id',$target)->count()===1 && GameAccount::find($target)->status==='sold',
                'approve' => $ok===1 && $validation===3 && $credits===1 && $debits===0 && $balances[0]==='150.00' && TopupReview::where('topup_id',$target)->count()===1,
                'retry' => $ok===4 && $topups===1 && count(array_unique(array_column($outputs,'id')))===1 && $credits===0 && (float)array_sum($balances)===0.0,
                'same_slip' => $ok===1 && $validation===3 && $topups===1 && $credits===0 && (float)array_sum($balances)===0.0,
            };
            $report['cases'][] = ['round'=>$round,'case'=>$kind,'passed'=>$casePass,'workers'=>$outputs,'balances'=>$balances,'topups'=>$topups,'credits'=>$credits,'debits'=>$debits];
            $passed = $passed && $casePass;
            echo "$round/$kind: ".($casePass?'PASS':'FAIL').PHP_EOL;
        }
    }
    $exit = $passed ? 0 : 1;
} catch (Throwable $error) {
    $report['error_type'] = get_class($error);
    $report['error_location'] = basename($error->getFile()).':'.$error->getLine();
    $report['database_error'] = $error instanceof \Illuminate\Database\QueryException ? ($error->errorInfo[2] ?? null) : null;
    $report['trace_locations'] = array_map(fn($frame)=>basename($frame['file'] ?? '').':'.($frame['line'] ?? ''),array_slice($error->getTrace(),0,12));
    echo 'Concurrency run could not finish; see report.'.PHP_EOL;
} finally {
    if (isset($gate) && $gate?->inTransaction()) $gate->rollBack();
    foreach ($processes as $p) if ($p->isRunning()) $p->stop();
    DB::purge('race');
    try {
        if ($createdDb) $adminPdo->exec("DROP DATABASE `$schema`");
        if ($createdUser) $adminPdo->exec("DROP USER '$username'@'127.0.0.1'");
        $report['database_and_user_removed'] = true;
    } catch (Throwable) { $report['cleanup_required'] = ['database'=>$schema,'user'=>$username]; $exit = 1; }
    $realDir = realpath($dir); $realBase = realpath($base);
    if ($realDir && $realBase && dirname($realDir)===$realBase && basename($realDir)===$id) {
        $report['temporary_files_removed'] = File::deleteDirectory($realDir);
        if (!$report['temporary_files_removed']) $exit = 1;
    }
}
$report['passed'] = $exit===0;
File::put($base.'/report-'.$id.'.json',json_encode($report,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
echo 'Report: '.$base.'/report-'.$id.'.json'.PHP_EOL;
exit($exit);
