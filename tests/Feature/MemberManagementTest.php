<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_management_requires_an_admin(): void
    {
        $member = User::factory()->create();
        $this->get(route('admin.members.index'))->assertRedirect(route('login'));
        $this->actingAs($member);
        foreach (['index', 'create', 'edit'] as $action) {
            $this->get(route('admin.members.'.$action, $action === 'edit' ? $member : []))->assertForbidden();
        }
        $this->post(route('admin.members.store'), [])->assertForbidden();
        $this->put(route('admin.members.update', $member), [])->assertForbidden();
    }

    public function test_admin_can_search_filter_and_open_forms(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['name' => 'สมาชิกทดสอบ', 'email' => 'find-me@example.com']);
        $this->actingAs($admin);
        $this->get(route('admin.members.index', ['q' => 'find-me', 'role' => 'user']))
            ->assertOk()->assertSee($member->email)->assertDontSee($admin->email);
        $this->get(route('admin.members.index', ['q' => (string) $member->id]))->assertOk()->assertSee($member->email);
        $this->get(route('admin.members.index', ['q' => 'find-me', 'role' => 'admin']))->assertOk()->assertSee('ไม่พบสมาชิกที่ตรงกับการค้นหา')->assertDontSee($member->email);
        $this->get(route('admin.members.create'))->assertOk();
        $this->get(route('admin.members.edit', $member))->assertOk()->assertSee($member->email);
        $this->get(route('admin.members.edit', $admin))->assertOk()->assertSee('บัญชีที่กำลังใช้งานไม่สามารถลดสิทธิ์ตัวเองได้');
    }

    public function test_admin_can_create_member_with_hashed_password_and_zero_balance(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.members.store'), [
                'name' => 'New Member', 'email' => 'new@example.com', 'role' => 'user',
                'password' => 'StrongPassword123', 'password_confirmation' => 'StrongPassword123', 'balance' => 999,
            ])->assertRedirect(route('admin.members.index'));
        $member = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('StrongPassword123', $member->password));
        $this->assertSame('0.00', $member->balance);
    }

    public function test_edit_preserves_wallet_and_password_and_clears_verification_on_email_change(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['balance' => 125]);
        $password = $member->password;
        $this->actingAs($admin)->put(route('admin.members.update', $member), [
            'name' => 'Updated', 'email' => 'updated@example.com', 'role' => 'admin',
            'balance' => 999, 'password' => 'InjectedPassword',
        ])->assertRedirect(route('admin.members.index'));
        $member->refresh();
        $this->assertSame('Updated', $member->name);
        $this->assertSame('updated@example.com', $member->email);
        $this->assertTrue($member->isAdmin());
        $this->assertNull($member->email_verified_at);
        $this->assertSame('125.00', $member->balance);
        $this->assertSame($password, $member->password);
        $this->put(route('admin.members.update', $member), [
            'name' => $member->name, 'email' => $member->email, 'role' => 'user',
        ])->assertSessionHasNoErrors();
        $this->assertFalse($member->fresh()->isAdmin());
    }

    public function test_validation_rejects_duplicates_invalid_roles_and_self_demotion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create();
        $this->actingAs($admin);
        $this->post(route('admin.members.store'), [
            'name' => 'Duplicate', 'email' => $member->email, 'role' => 'owner',
            'password' => 'short', 'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['email', 'role', 'password']);
        $this->put(route('admin.members.update', $member), [
            'name' => 'Duplicate', 'email' => $admin->email, 'role' => 'user',
        ])->assertSessionHasErrors('email');
        $this->put(route('admin.members.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'user',
        ])->assertSessionHasErrors('role');
        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_pagination_preserves_search_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(21)->create(['name' => 'Paged Member']);
        $this->actingAs($admin)->get(route('admin.members.index', ['q' => 'Paged', 'role' => 'user']))
            ->assertOk()->assertViewHas('members', fn ($members) => $members->total() === 21
                && $members->count() === 20 && str_contains($members->nextPageUrl(), 'q=Paged')
                && str_contains($members->nextPageUrl(), 'role=user'));
    }
}
