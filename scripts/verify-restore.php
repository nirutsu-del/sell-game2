<?php

// Run only from CLI: php scripts/verify-restore.php <backup-directory>
// Imports with a temporary MySQL user granted access ONLY to a new random database.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

$backup = realpath($argv[1] ?? '');
if (!$backup || !is_file($backup.'/manifest.json') || !is_file($backup.'/database.sql')) {
    fwrite(STDERR, "A completed backup directory is required.\n"); exit(1);
}
$id = bin2hex(random_bytes(6));
$schema = 'mizuki_restore_'.$id;
$username = 'restore_'.$id;
$password = bin2hex(random_bytes(24));
$work = storage_path('app/restore-tests/'.$id);
$report = ['backup' => basename($backup), 'tested_at' => date(DATE_ATOM), 'checks' => [], 'limitations' => [
    'CLI verification only; no browser checkout or external payments/emails were run.',
    'Comparison with current store is meaningful only if no writes occurred since backup.',
]];
$admin = null;
$createdDb = $createdUser = false;
$exit = 1;
try {
    $config = DB::connection()->getConfig();
    if (!in_array($config['driver'], ['mysql','mariadb'], true)) throw new RuntimeException('MySQL required');
    $admin = DB::connection()->getPdo();
    $admin->exec("CREATE DATABASE `$schema` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $createdDb = true;
    $admin->exec("CREATE USER '$username'@'127.0.0.1' IDENTIFIED BY ".$admin->quote($password));
    $createdUser = true;
    $admin->exec("GRANT ALL PRIVILEGES ON `$schema`.* TO '$username'@'127.0.0.1'");
    // MySQL may match localhost instead of 127.0.0.1; connect via TCP consistently.
    $args = ['C:\\xampp\\mysql\\bin\\mysql.exe', '--protocol=TCP', '--host='.$config['host'],
        '--port='.$config['port'], '--user='.$username, '--database='.$schema, '--binary-mode=1'];
    if (!is_file($args[0])) $args[0] = 'mysql';
    $input = fopen($backup.'/database.sql', 'rb');
    $process = new Process($args, null, ['MYSQL_PWD' => $password], $input, 120);
    try { $process->run(); } finally { fclose($input); }
    if (!$process->isSuccessful()) throw new RuntimeException('Restricted SQL import failed');
    $report['checks']['restricted_sql_import'] = true;

    config(['database.connections.restore_verify' => array_merge($config, [
        'url' => null, 'database' => $schema, 'username' => $username, 'password' => $password,
    ])]);
    $restored = DB::connection('restore_verify');
    $tables = $restored->select('SHOW TABLES');
    $allEqual = true;
    foreach ($tables as $entry) {
        $table = array_values((array) $entry)[0];
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) throw new RuntimeException('Unexpected table identifier');
        $rows = $restored->table($table)->get();
        $report['table_counts'][$table] = count($rows);
        // Sort hashes so comparison does not depend on storage order. Never output row data.
        $digest = static function ($rows) {
            $hashes = [];
            foreach ($rows as $row) $hashes[] = hash('sha256', json_encode($row, JSON_THROW_ON_ERROR));
            sort($hashes); return hash('sha256', implode('', $hashes));
        };
        $equal = $digest($rows) === $digest(DB::connection()->table($table)->get());
        $report['table_matches_current'][$table] = $equal;
        if (!in_array($table, ['sessions', 'cache', 'cache_locks'], true)) $allEqual = $allEqual && $equal;
    }
    $report['checks']['business_rows_match_current_store'] = $allEqual;
    $report['comparison_excludes'] = ['sessions', 'cache', 'cache_locks'];
    $report['checks']['wallet_total_matches'] = (string) $restored->table('users')->sum('balance') === (string) DB::table('users')->sum('balance');
    $decrypted = 0;
    foreach (['game_accounts' => 'credentials_data', 'purchase_histories' => 'account_data_delivered'] as $table => $field) {
        foreach ($restored->table($table)->whereNotNull($field)->pluck($field) as $value) {
            $plain = $table === 'purchase_histories' ? Crypt::decrypt($value) : Crypt::decryptString($value);
            json_decode($plain, true, 512, JSON_THROW_ON_ERROR);
            $decrypted++;
        }
    }
    $report['decrypted_records'] = $decrypted;
    $report['checks']['original_app_key_decrypts_records'] = $decrypted > 0;

    File::ensureDirectoryExists($work, 0700);
    $manifest = json_decode(file_get_contents($backup.'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    $fileCount = 0;
    foreach (['storage_public' => 'files', 'private_slips' => 'private_slips'] as $folder => $key) {
        if (!is_dir($backup.'/'.$folder)) {
            if (!empty($manifest[$key])) throw new RuntimeException('Missing backup folder');
            continue;
        }
        if (!File::copyDirectory($backup.'/'.$folder, $work.'/'.$folder)) throw new RuntimeException('Copy failed');
        foreach ($manifest[$key] ?? [] as $relative) {
            $relative = str_replace('\\', '/', $relative);
            if (str_contains($relative, '..') || str_starts_with($relative, '/')) throw new RuntimeException('Unsafe manifest path');
            $source = $backup.'/'.$folder.'/'.$relative;
            $target = $work.'/'.$folder.'/'.$relative;
            if (!is_file($target) || hash_file('sha256', $source) !== hash_file('sha256', $target)) throw new RuntimeException('Restored file differs');
            if (@getimagesize($target) === false) throw new RuntimeException('Image cannot be decoded');
            $fileCount++;
        }
    }
    $report['verified_image_files'] = $fileCount;
    $report['checks']['restored_files_match_backup'] = $fileCount > 0;
    $missing = 0;
    foreach ($restored->table('topup_transactions')->whereNotNull('slip_path')->pluck('slip_path') as $path) {
        if (!is_file($work.'/private_slips/'.preg_replace('~^slips/~', '', $path))) $missing++;
    }
    foreach ($restored->table('game_accounts')->pluck('images') as $json) {
        foreach (json_decode($json ?: '[]', true, 512, JSON_THROW_ON_ERROR) as $path) {
            if (!is_file($work.'/storage_public/'.$path)) $missing++;
        }
    }
    $report['missing_account_images_or_slips'] = $missing;
    $report['checks']['account_and_slip_references_exist'] = $missing === 0;
    $exit = in_array(false, $report['checks'], true) ? 1 : 0;
} catch (Throwable $e) {
    // Do not expose SQL, credentials or decrypted customer data in diagnostics.
    $report['error_type'] = get_class($e);
    $report['checks']['completed'] = false;
} finally {
    DB::purge('restore_verify');
    try {
        if ($createdDb) $admin->exec("DROP DATABASE `$schema`");
        if ($createdUser) $admin->exec("DROP USER '$username'@'127.0.0.1'");
        $report['temporary_database_and_user_removed'] = true;
    } catch (Throwable) {
        $report['cleanup_required'] = ['database' => $schema, 'user' => $username]; $exit = 1;
    }
    $resolved = realpath($work);
    $parent = realpath(storage_path('app/restore-tests'));
    if ($resolved && $parent && dirname($resolved) === $parent && basename($resolved) === $id) {
        $report['temporary_files_removed'] = File::deleteDirectory($resolved);
    }
}
$report['passed'] = $exit === 0;
File::ensureDirectoryExists(storage_path('app/restore-tests'), 0700);
$path = storage_path('app/restore-tests/report_'.$id.'.json');
File::put($path, json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
echo json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
echo 'Report: '.$path.PHP_EOL;
exit($exit);
