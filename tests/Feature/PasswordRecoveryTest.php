<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, Notification, Password};
use Tests\TestCase;
class PasswordRecoveryTest extends TestCase {
    use RefreshDatabase;
    public function test_reset_request_is_generic_and_email_uses_configured_host(): void {
        Notification::fake();
        config(['app.url'=>'https://store.example']);
        $user = User::factory()->create();
        $this->get(route('password.request'))->assertOk();
        $known = $this->post(route('password.email'),['email'=>$user->email]);
        $known->assertSessionHas('success');
        $message = session('success');
        $this->post(route('password.email'),['email'=>'unknown@example.com'])->assertSessionHas('success',$message);
        Notification::assertSentTo($user,ResetPassword::class,function ($notice) use ($user) {
            $mail = $notice->toMail($user);
            $this->assertStringStartsWith('https://store.example/reset-password/',$mail->actionUrl);
            return true;
        });
    }
    public function test_valid_reset_changes_password_and_token_cannot_be_reused(): void {
        $user = User::factory()->create(['password'=>'old-password']);
        $token = Password::createToken($user);
        $this->get(route('password.reset',['token'=>$token,'email'=>$user->email]))->assertOk();
        $body = ['email'=>$user->email,'token'=>$token,'password'=>'new-password','password_confirmation'=>'new-password'];
        $this->post(route('password.store'),$body)->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('new-password',$user->fresh()->password));
        $this->post(route('password.store'),$body)->assertSessionHasErrors('email');
    }
    public function test_invalid_expired_and_mismatched_tokens_do_not_change_password(): void {
        $user = User::factory()->create(['password'=>'original-password']);
        $token = Password::createToken($user);
        $body = ['email'=>$user->email,'token'=>'invalid','password'=>'new-password','password_confirmation'=>'new-password'];
        $this->post(route('password.store'),$body)->assertSessionHasErrors('email');
        $body['token'] = $token;
        $this->travel(61)->minutes();
        $this->post(route('password.store'),$body)->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('original-password',$user->fresh()->password));
    }
    public function test_change_requires_current_password_and_logs_out(): void {
        $user = User::factory()->create(['password'=>'old-password']);
        $resetToken = Password::createToken($user);
        $body = ['current_password'=>'wrong','password'=>'new-password','password_confirmation'=>'new-password'];
        $this->actingAs($user)->get(route('password.edit'))->assertOk();
        $this->put(route('password.update'),$body)->assertSessionHasErrors('current_password');
        $body['current_password'] = 'old-password';
        $this->put(route('password.update'),$body)->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertTrue(Hash::check('new-password',$user->fresh()->password));
        $this->assertFalse(Password::tokenExists($user,$resetToken));
    }
    public function test_unauthenticated_change_is_blocked_and_request_rate_is_limited(): void {
        Notification::fake();
        $this->get(route('password.edit'))->assertRedirect(route('login'));
        for ($i=0;$i<5;$i++) $this->post(route('password.email'),['email'=>'nobody@example.com'])->assertRedirect();
        $this->post(route('password.email'),['email'=>'nobody@example.com'])->assertStatus(429);
    }
}
