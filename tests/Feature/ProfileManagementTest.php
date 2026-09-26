<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_update_own_name_without_changing_protected_fields_or_another_member(): void
    {
        $user = User::factory()->create(['name' => 'ชื่อเดิม', 'role' => 'user', 'balance' => 120]);
        $other = User::factory()->create(['name' => 'สมาชิกอื่น']);
        $original = $user->refresh()->getRawOriginal();
        $this->actingAs($user)->get(route('user.profile.edit'))->assertOk()->assertSee($user->email)->assertSee('ชื่อเดิม');
        $this->put(route('user.profile.update'), [
            'name' => 'ชื่อใหม่', 'id' => $other->id, 'email' => 'changed@example.com',
            'role' => 'admin', 'balance' => 99999, 'status' => 'suspended', 'password' => 'new-password',
        ])->assertRedirect(route('user.profile.edit'))->assertSessionHas('success');
        $user->refresh();
        $this->assertSame('ชื่อใหม่', $user->name);
        foreach (['email', 'role', 'balance', 'status', 'password'] as $field) {
            $this->assertSame($original[$field], $user->getRawOriginal($field));
        }
        $this->assertSame('สมาชิกอื่น', $other->fresh()->name);
        $this->get(route('user.dashboard'))->assertOk()->assertSee('สวัสดี, ชื่อใหม่')->assertSee(route('user.profile.edit'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_and_suspended_member_cannot_access_profile(): void
    {
        $this->get(route('user.profile.edit'))->assertRedirect(route('login'));
        $this->put(route('user.profile.update'), ['name' => 'Changed'])->assertRedirect(route('login'));
        $user = User::factory()->create(['status' => 'suspended', 'name' => 'Original']);
        $this->actingAs($user)->put(route('user.profile.update'), ['name' => 'Changed'])->assertRedirect(route('login'));
        $this->assertSame('Original', $user->fresh()->name);
    }

    public function test_invalid_names_return_to_profile_and_displayed_names_are_escaped(): void
    {
        $user = User::factory()->create(['name' => 'Original']);
        $this->actingAs($user);
        foreach (['', '   ', str_repeat('ก', 101), ['invalid']] as $name) {
            $this->from(route('notifications.index'))->put(route('user.profile.update'), ['name' => $name])
                ->assertRedirect(route('user.profile.edit'))->assertSessionHasErrors('name');
            $this->get(route('user.profile.edit'))->assertOk();
        }
        $this->assertSame('Original', $user->fresh()->name);
        $this->put(route('user.profile.update'), ['name' => '<script>alert(1)</script>'])->assertSessionHasNoErrors();
        $this->get(route('user.profile.edit'))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }
}
