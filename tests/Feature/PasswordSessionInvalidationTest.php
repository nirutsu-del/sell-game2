<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Auth, Hash, Password};
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PasswordSessionInvalidationTest extends TestCase
{
    use RefreshDatabase;

    private ?string $sessionDirectory = null;

    protected function tearDown(): void
    {
        if ($this->sessionDirectory) {
            foreach (glob($this->sessionDirectory.'/*') as $file) {
                unlink($file);
            }
            rmdir($this->sessionDirectory);
        }
        parent::tearDown();
    }

    public static function flows(): array
    {
        return [['file', 'change'], ['file', 'reset'], ['database', 'change'], ['database', 'reset']];
    }

    // Separate encrypted cookie jars and fresh guards/stores model independent devices.
    private function visit(array &$cookies, string $method, string $uri, array $data = [])
    {
        Auth::forgetGuards();
        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
        $this->app['cookie']->flushQueuedCookies();
        $response = $this->call($method, $uri, $data, $cookies);
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getExpiresTime() !== 0 && $cookie->getExpiresTime() < time()) {
                unset($cookies[$cookie->getName()]);
            } else {
                $cookies[$cookie->getName()] = $cookie->getValue();
            }
        }
        return $response;
    }

    #[DataProvider('flows')]
    public function test_old_device_and_remember_cookie_are_rejected_after_password_change_or_reset(string $driver, string $flow): void
    {
        $this->sessionDirectory = sys_get_temp_dir().'/mizuki-session-test-'.bin2hex(random_bytes(8));
        mkdir($this->sessionDirectory);
        config(['session.driver' => $driver, 'session.files' => $this->sessionDirectory]);
        $user = User::factory()->create(['password' => 'old-password']);
        $otherDevice = [];
        $this->visit($otherDevice, 'POST', route('login.store'), [
            'email' => $user->email, 'password' => 'old-password', 'remember' => true,
        ])->assertRedirect();
        $rememberName = Auth::guard()->getRecallerName();
        $this->assertArrayHasKey($rememberName, $otherDevice);
        $rememberOnly = [$rememberName => $otherDevice[$rememberName]];
        $rememberProbe = $rememberOnly;
        $this->visit($rememberProbe, 'GET', route('user.dashboard'))->assertOk();
        $this->visit($otherDevice, 'GET', route('user.dashboard'))->assertOk();
        $oldToken = $user->fresh()->remember_token;
        $this->assertNotEmpty($oldToken);

        $currentDevice = [];
        $body = ['password' => 'new-password', 'password_confirmation' => 'new-password'];
        if ($flow === 'change') {
            $this->visit($currentDevice, 'POST', route('login.store'), [
                'email' => $user->email, 'password' => 'old-password',
            ])->assertRedirect();
            $this->visit($currentDevice, 'PUT', route('password.update'), $body + [
                'current_password' => 'old-password',
            ])->assertRedirect(route('login'))->assertSessionHasNoErrors();
        } else {
            $this->visit($currentDevice, 'POST', route('password.store'), $body + [
                'email' => $user->email, 'token' => Password::createToken($user),
            ])->assertRedirect(route('login'))->assertSessionHasNoErrors();
        }

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertNotSame($oldToken, $user->fresh()->remember_token);
        $this->visit($otherDevice, 'GET', route('user.dashboard'))->assertRedirect(route('login'));
        $this->visit($rememberOnly, 'GET', route('user.dashboard'))->assertRedirect(route('login'));
        $this->visit($currentDevice, 'GET', route('user.dashboard'))->assertRedirect(route('login'));
        $this->visit($currentDevice, 'POST', route('login.store'), [
            'email' => $user->email, 'password' => 'new-password',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->visit($currentDevice, 'GET', route('user.dashboard'))->assertOk();
    }
}
