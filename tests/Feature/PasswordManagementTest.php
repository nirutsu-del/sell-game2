<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, Notification, Password};
use Tests\TestCase;

class PasswordManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_recovery_pages_and_submission_are_removed_even_with_an_old_token(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password'=>'old-password']);
        $token = Password::createToken($user);
        $this->get(route('login'))->assertOk()->assertDontSee('ลืมรหัสผ่าน')->assertDontSee('/forgot-password');
        foreach ([false, true] as $authenticated) {
            if ($authenticated) $this->actingAs($user);
            $this->get('/forgot-password')->assertNotFound();
            $this->post('/forgot-password', ['email'=>$user->email])->assertNotFound();
            $this->get('/reset-password/'.$token.'?email='.urlencode($user->email))->assertNotFound();
            $this->post('/reset-password', ['email'=>$user->email,'token'=>$token,
                'password'=>'new-password','password_confirmation'=>'new-password'])->assertNotFound();
        }
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        Notification::assertNothingSent();
    }

    public function test_change_requires_current_password_and_logs_out(): void
    {
        $user = User::factory()->create(['password'=>'old-password']);
        $oldResetToken = Password::createToken($user);
        $body = ['current_password'=>'wrong','password'=>'new-password','password_confirmation'=>'new-password'];
        $this->actingAs($user)->get(route('password.edit'))->assertOk();
        $this->put(route('password.update'),$body)->assertSessionHasErrors('current_password');
        $body['current_password'] = 'old-password';
        $this->put(route('password.update'),$body)->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertTrue(Hash::check('new-password',$user->fresh()->password));
        $this->assertFalse(Password::tokenExists($user,$oldResetToken));
    }

    public function test_change_requires_login_and_is_rate_limited(): void
    {
        $this->get(route('password.edit'))->assertRedirect(route('login'));
        $this->put(route('password.update'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        for ($i=0; $i<5; $i++) {
            $this->put(route('password.update'),['current_password'=>'wrong','password'=>'new-password',
                'password_confirmation'=>'new-password'])->assertSessionHasErrors('current_password');
        }
        $this->put(route('password.update'))->assertStatus(429);
    }
}
