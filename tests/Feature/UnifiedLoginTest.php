<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Auth\Pages\Login;
use App\Models\Member;
use App\Models\User;
use App\Notifications\VerifyAdminEmailAuthentication;
use App\Support\RoleDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class UnifiedLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_and_legacy_login_urls_redirect_to_unified_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/admin/login')->assertRedirect('/login');
        $this->get('/portal/login')->assertRedirect('/login');
        $this->get('/pos/login')->assertRedirect('/login');
        $this->get('/cashier/login')->assertRedirect('/login');
        $this->get('/pos/canteen/login')->assertRedirect('/login');
    }

    public function test_unified_login_page_is_reachable(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_collection_cashier_is_sent_to_cashier_panel(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::CollectionCashier,
            'email' => 'collection-login@example.com',
        ]);

        $this->authenticate($user->email)->assertRedirect(RoleDashboard::url($user));
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_grocery_cashier_is_sent_to_pos_panel(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Cashier,
            'email' => 'grocery-login@example.com',
        ]);

        $this->authenticate($user->email)->assertRedirect(RoleDashboard::url($user));
    }

    public function test_canteen_cashier_is_sent_to_canteen_panel(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::CanteenCashier,
            'email' => 'canteen-login@example.com',
        ]);

        $this->authenticate($user->email)->assertRedirect(RoleDashboard::url($user));
    }

    public function test_admin_email_code_is_sent_on_login(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'admin-login@example.com',
        ]);

        $component = $this->authenticate($user->email);

        $this->assertNotEmpty($component->get('userUndertakingMultiFactorAuthentication'));
        $this->assertGuest('web');
        Notification::assertSentTo($user, VerifyAdminEmailAuthentication::class);
    }

    public function test_remembered_admin_skips_email_code_for_one_day(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'admin-remember@example.com',
        ]);

        $user->forceFill([
            'email_mfa_verified_until' => now()->addDay(),
        ])->save();

        $this->authenticate($user->email)->assertRedirect(RoleDashboard::url($user));
        $this->assertAuthenticatedAs($user, 'web');
        Notification::assertNothingSent();
    }

    public function test_inventory_user_is_sent_to_pos_panel(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Inventory,
            'email' => 'inventory-login@example.com',
        ]);

        $this->assertSame('pos', RoleDashboard::panelId($user));
        $this->authenticate($user->email)->assertRedirect(RoleDashboard::url($user));
    }

    public function test_verified_member_is_sent_to_portal(): void
    {
        $member = Member::factory()->create([
            'email' => 'member-login@example.com',
        ]);

        $this->authenticate($member->email)
            ->assertRedirect(RoleDashboard::url($member));
        $this->assertAuthenticatedAs($member, 'member');
    }

    public function test_disabled_account_cannot_sign_in(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::CollectionCashier,
            'email' => 'disabled-login@example.com',
            'is_active' => false,
        ]);

        $this->authenticate($user->email)
            ->assertHasErrors(['data.email']);
        $this->assertGuest('web');
    }

    public function test_unverified_member_cannot_sign_in(): void
    {
        $member = Member::factory()->create([
            'email' => 'unverified-login@example.com',
            'email_verified_at' => null,
        ]);

        $this->authenticate($member->email)
            ->assertHasErrors(['data.email']);
        $this->assertGuest('member');
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'wrong-password@example.com',
        ]);

        $this->authenticate($user->email, 'NotThePassword1')
            ->assertHasErrors(['data.email']);
        $this->assertGuest('web');
    }

    private function authenticate(string $email, string $password = 'password'): Testable
    {
        filament()->setCurrentPanel(filament()->getPanel('auth'));

        return Livewire::test(Login::class)
            ->fillForm([
                'email' => $email,
                'password' => $password,
            ])
            ->call('authenticate');
    }
}
