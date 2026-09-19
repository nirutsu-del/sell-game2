<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class StoreSecurityCheck extends Command
{
    protected $signature = 'store:security-check';
    protected $description = 'Read-only production configuration checks (never prints secrets)';

    public function handle(): int
    {
        $db = config('database.connections.'.config('database.default'), []);
        $checks = [
            'APP_ENV is production' => app()->environment('production'),
            'APP_DEBUG is disabled' => !config('app.debug'),
            'APP_KEY is configured (keep original key for recovery)' => filled(config('app.key')),
            'APP_URL uses HTTPS' => parse_url(config('app.url'), PHP_URL_SCHEME) === 'https',
            'Session cookies are secure' => (bool) config('session.secure'),
            'Session cookies are HTTP-only' => (bool) config('session.http_only'),
            'Session SameSite is lax or strict' => in_array(config('session.same_site'), ['lax', 'strict'], true),
            'Database user is not root and has a password' => !in_array($db['username'] ?? '', ['', 'root'], true) && filled($db['password'] ?? null),
        ];

        foreach ($checks as $label => $passed) {
            $this->line(($passed ? '[PASS] ' : '[FAIL] ').$label);
        }
        $this->warn('Manual checks still required: public/ document root, HTTPS, file permissions, scheduler, off-site backups and isolated restore test.');

        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
