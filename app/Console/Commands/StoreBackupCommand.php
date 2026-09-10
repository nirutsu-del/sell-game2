<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

class StoreBackupCommand extends Command
{
    protected $signature = 'store:backup {--output= : Directory for the backup} {--database-only : Skip uploaded files}';
    protected $description = 'Back up the Mizuki Shop database and uploaded files';

    public function handle(): int
    {
        $stamp = now('Asia/Bangkok')->format('Ymd_His');
        $root = $this->option('output') ?: storage_path('app/backups');
        $directory = null;
        $manifest = ['created_at'=>now('Asia/Bangkok')->toIso8601String(),'app_url'=>config('app.url'),'database'=>config('database.default'),'files'=>[]];

        try {
            if (!$this->option('output')) File::ensureDirectoryExists($root, 0700);
            $resolved = realpath($root);
            if ($resolved === false || !is_dir($resolved)) throw new RuntimeException('Output must be an existing private directory.');
            $normalized = strtolower(str_replace('\\', '/', $resolved));
            $project = strtolower(str_replace('\\', '/', realpath(base_path())));
            $default = strtolower(str_replace('\\', '/', realpath(storage_path('app/backups')) ?: storage_path('app/backups')));
            $public = strtolower(str_replace('\\', '/', realpath(public_path())));
            $uploads = strtolower(str_replace('\\', '/', realpath(storage_path('app/public')) ?: storage_path('app/public')));
            if ($normalized === $public || str_starts_with($normalized, $public.'/') ||
                $normalized === $uploads || str_starts_with($normalized, $uploads.'/') ||
                (($normalized === $project || str_starts_with($normalized, $project.'/')) && $normalized !== $default)) {
                throw new RuntimeException('Use the default backup directory or a private directory outside the project.');
            }
            $directory = $resolved.DIRECTORY_SEPARATOR.'store_'.$stamp.'_'.bin2hex(random_bytes(6));
            if (!mkdir($directory, 0700)) throw new RuntimeException('Cannot create backup directory.');
            $this->dumpDatabase($directory.DIRECTORY_SEPARATOR.'database.sql');
            if (!$this->option('database-only')) {
                $source = storage_path('app/public');
                $destination = $directory.DIRECTORY_SEPARATOR.'storage_public';
                if (File::isDirectory($source)) {
                    if (!File::copyDirectory($source,$destination)) throw new RuntimeException('Cannot copy public uploads.');
                    $manifest['files'] = $this->files($destination);
                }
                $private = storage_path('app/private/slips');
                if (File::isDirectory($private)) {
                    if (!File::copyDirectory($private,$directory.DIRECTORY_SEPARATOR.'private_slips')) throw new RuntimeException('Cannot copy private slips.');
                    $manifest['private_slips'] = $this->files($directory.DIRECTORY_SEPARATOR.'private_slips');
                }
            }
            $manifest['requires_original_app_key'] = true;
            if (File::put($directory.DIRECTORY_SEPARATOR.'manifest.json', json_encode($manifest, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)) === false) {
                throw new RuntimeException('Cannot write backup manifest.');
            }
            $this->info('Backup created: '.$directory);
            $this->line('Database: '.filesize($directory.DIRECTORY_SEPARATOR.'database.sql').' bytes');
            $this->line('Uploaded files: '.count($manifest['files']));
            $this->line('Private slips: '.count($manifest['private_slips'] ?? []));
            return self::SUCCESS;
        } catch (\Throwable $e) {
            // Never recursively delete an output directory after a failed export.
            $this->error('Backup failed: '.$e->getMessage());
            if ($directory) $this->warn('Incomplete backup (do not restore): '.$directory);
            return self::FAILURE;
        }
    }

    protected function dumpDatabase(string $file): void
    {
        $connection = config('database.default');
        $config = config('database.connections.'.$connection);
        if (!in_array($config['driver'] ?? null,['mysql','mariadb'],true)) throw new RuntimeException('This SQL backup command supports MySQL and MariaDB only.');
        $binary = PHP_OS_FAMILY === 'Windows' ? 'C:\\xampp\\mysql\\bin\\mysqldump.exe' : 'mysqldump';
        if (PHP_OS_FAMILY === 'Windows' && !is_file($binary)) $binary = 'mysqldump';
        $args = [$binary,'--single-transaction','--quick','--skip-lock-tables','--host='.$config['host'],'--port='.$config['port'],'--user='.$config['username'],'--result-file='.$file,$config['database']];
        // Credentials stay out of command-line arguments and console output.
        $process = new Process($args, null, ['MYSQL_PWD' => (string) ($config['password'] ?? '')], null, 300);
        try { $code = $process->run(); }
        catch (\Throwable) { throw new RuntimeException('Database export could not complete. Check MySQL access and mysqldump installation.'); }
        if ($code !== 0 || !is_file($file) || filesize($file) === 0) throw new RuntimeException('Database export failed. Check MySQL access and output directory permissions.');
    }
    private function files(string $root): array
    {
        if (!File::isDirectory($root)) return [];
        return collect(File::allFiles($root))->map(fn($file)=>str_replace($root.DIRECTORY_SEPARATOR,'',$file->getPathname()))->values()->all();
    }
}
