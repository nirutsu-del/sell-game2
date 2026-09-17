<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_limits_normalized_email_and_recovers_after_one_minute(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'test@example.com', 'password' => 'wrong'])->assertRedirect();
        }
        $this->post('/login', ['email' => 'TEST@example.com', 'password' => 'wrong'])->assertStatus(429);
        $this->post('/login', ['email' => 'another@example.com', 'password' => 'wrong'])->assertRedirect();
        $this->travel(61)->seconds();
        $this->post('/login', ['email' => 'test@example.com', 'password' => 'wrong'])->assertRedirect();
    }

    public function test_ip_limit_blocks_rotating_email_addresses(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->post('/login', ['email' => "user{$i}@example.com", 'password' => 'wrong'])->assertRedirect();
        }
        $this->post('/login', ['email' => 'next@example.com', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_customer_cannot_access_admin_reads_writes_or_slips(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $user = User::factory()->create(['role' => 'user']);
        $topup = \App\Models\TopupTransaction::create([
            'user_id' => $user->id, 'amount' => 100,
            'payment_method' => 'promptpay_slip', 'reference_no' => 'SECURITY-TEST',
        ]);
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->get('/admin/reports/sales/export')->assertForbidden();
        $this->get('/admin/topups/'.$topup->id.'/slip')->assertForbidden();
        $this->put('/admin/settings', ['name' => 'hacked'])->assertForbidden();
        $this->post('/admin/topups/'.$topup->id.'/approve')->assertForbidden();
        $this->assertSame('pending', $topup->fresh()->status);
    }

    public function test_security_headers_and_private_cache_policy(): void
    {
        $response = $this->get('/login')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertHeaderMissing('Strict-Transport-Security');
        $this->actingAs(User::factory()->create())->get('/wallet')->assertOk();
        $this->assertStringContainsString('no-store', $this->get('/wallet')->headers->get('Cache-Control'));
    }

    public function test_security_check_flags_development_config_without_printing_secrets(): void
    {
        config(['app.debug' => true, 'session.secure' => false, 'mail.default' => 'log']);
        $this->artisan('store:security-check')->expectsOutput('[FAIL] APP_DEBUG is disabled')->assertExitCode(1);
    }

    public function test_csp_for_built_assets_restricts_sources_and_preserves_inline_compatibility(): void
    {
        $this->withoutVite();
        $policy = $this->get('/login')->assertOk()->headers->get('Content-Security-Policy');
        foreach (["default-src 'self'", "script-src 'self' 'unsafe-inline'", "connect-src 'self'",
            "object-src 'none'", "base-uri 'self'", "form-action 'self'", "frame-ancestors 'self'"] as $directive) {
            $this->assertContains($directive, explode('; ', $policy));
        }
        $this->assertStringNotContainsString('localhost:', $policy);
    }

    public function test_csp_allows_active_vite_origin_only_in_local_environment(): void
    {
        $vite = app(\Illuminate\Foundation\Vite::class);
        $originalHotFile = $vite->hotFile();
        $hotFile = tempnam(sys_get_temp_dir(), 'mizuki-vite-');
        file_put_contents($hotFile, 'http://localhost:5173');
        $vite->useHotFile($hotFile);
        $middleware = new \App\Http\Middleware\SecurityHeaders();
        try {
            $this->app->instance('env', 'local');
            $policy = $middleware->handle(\Illuminate\Http\Request::create('/'), fn () => response('test'))
                ->headers->get('Content-Security-Policy');
            $this->assertStringContainsString("script-src 'self' 'unsafe-inline' http://localhost:5173;", $policy);
            $this->assertStringContainsString("style-src 'self' 'unsafe-inline' http://localhost:5173;", $policy);
            $this->assertStringContainsString("connect-src 'self' http://localhost:5173 ws://localhost:5173;", $policy);

            $this->app->instance('env', 'production');
            $policy = $middleware->handle(\Illuminate\Http\Request::create('/'), fn () => response('test'))
                ->headers->get('Content-Security-Policy');
            $this->assertStringNotContainsString('localhost:5173', $policy);
        } finally {
            $this->app->instance('env', 'testing');
            $vite->useHotFile($originalHotFile);
            unlink($hotFile);
        }
    }

    public function test_backup_refuses_public_output_without_creating_files(): void
    {
        $before = glob(public_path('store_*'));
        $this->artisan('store:backup', ['--output' => public_path()])->assertExitCode(1);
        $this->assertSame($before, glob(public_path('store_*')));
        $this->artisan('store:backup', ['--output' => storage_path('app/public')])->assertExitCode(1);
    }
}
