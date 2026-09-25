<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_registration_have_separate_forms(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('action="'.route('login.store').'"', false)
            ->assertSee('href="'.route('register.form').'"', false)
            ->assertDontSee('name="password_confirmation"', false);

        $this->get(route('register.form'))->assertOk()
            ->assertSee('action="'.route('register').'"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertDontSee('action="'.route('login.store').'"', false);
    }

    public function test_invalid_registration_returns_to_registration_and_preserves_name(): void
    {
        $this->from(route('register.form'))->post(route('register'), [
            'name' => 'Player', 'email' => 'player@example.test',
            'password' => 'password123', 'password_confirmation' => 'different',
        ])->assertRedirect(route('register.form'))->assertSessionHasErrors('password');

        $this->get(route('register.form'))->assertOk()
            ->assertSee('value="Player"', false)
            ->assertDontSee('value="password123"', false);
        $this->assertGuest();
    }
}
