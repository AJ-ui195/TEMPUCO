<?php

namespace Tests\Feature;

use App\Auth\Http\Responses\FilamentLoginResponse;
use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\Member;
use App\Models\User;
use App\Notifications\VerifyMemberAccount;
use App\Support\MemberAccount;
use App\Support\StaffAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AccountRegistrationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_weak_member_password_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        MemberAccount::create([
            'name' => 'New Member',
            'email' => 'new-member@example.com',
            'sex' => 'female',
            'civil_status' => 'single',
            'address' => 'Temuco',
            'contact_number' => '09170000000',
            'occupation' => 'Teacher',
            'employer_department' => 'DepEd',
            'password' => 'password',
        ]);
    }

    public function test_numeric_only_password_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        MemberAccount::create([
            'name' => 'New Member',
            'email' => 'new-member-2@example.com',
            'sex' => 'female',
            'civil_status' => 'single',
            'address' => 'Temuco',
            'contact_number' => '09170000001',
            'occupation' => 'Teacher',
            'employer_department' => 'DepEd',
            'password' => '12345678',
        ]);
    }

    public function test_strong_member_password_creates_account_that_must_change_password(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);

        $member = MemberAccount::create([
            'name' => 'New Member',
            'email' => 'strong-member@example.com',
            'sex' => 'female',
            'civil_status' => 'single',
            'address' => 'Temuco',
            'contact_number' => '09170000002',
            'occupation' => 'Teacher',
            'employer_department' => 'DepEd',
            'password' => 'Password12',
        ]);

        $this->assertTrue($member->must_change_password);
        $this->assertTrue($member->is_active);
        $this->assertNull($member->email_verified_at);
        $this->assertFalse($member->canAccessPanel(filament()->getPanel('user')));
        Notification::assertSentTo($member, VerifyMemberAccount::class);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.created',
            'subject_id' => $member->id,
        ]);
    }

    public function test_member_account_requires_email_confirmation_before_portal_access(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);

        $member = MemberAccount::create($this->memberPayload());
        $url = null;

        Notification::assertSentTo(
            $member,
            VerifyMemberAccount::class,
            function (VerifyMemberAccount $notification) use (&$url): bool {
                $url = $notification->verificationUrl;

                return true;
            },
        );

        $this->assertIsString($url);
        $this->get($url)->assertOk()->assertSee('Email confirmed', false);

        $member->refresh();
        $this->assertNotNull($member->email_verified_at);
        $this->assertTrue($member->canAccessPanel(filament()->getPanel('user')));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'member.email_confirmed',
            'subject_id' => $member->id,
        ]);
    }

    public function test_unsigned_member_confirmation_link_is_rejected(): void
    {
        $member = Member::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->get(route('members.verify-email', ['member' => $member, 'token' => 'test-token']))
            ->assertForbidden();
    }

    public function test_staff_creation_requires_strong_password_and_is_audited(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);

        $this->expectException(ValidationException::class);
        StaffAccount::create([
            'name' => 'New Cashier',
            'email' => 'new-cashier@example.com',
            'role' => UserRole::Cashier,
            'password' => 'password',
        ]);
    }

    public function test_staff_account_is_created_with_must_change_password(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);

        $staff = StaffAccount::create([
            'name' => 'New Cashier',
            'email' => 'new-cashier@example.com',
            'role' => UserRole::Cashier,
            'password' => 'Password12',
        ]);

        $this->assertTrue($staff->must_change_password);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'staff.created',
            'subject_id' => $staff->id,
        ]);
        $this->assertFalse(Member::query()->where('email', $staff->email)->exists());
        $this->assertTrue(UserResource::shouldRegisterNavigation());
        $this->assertTrue(
            UserResource::getEloquentQuery()->whereKey($staff->id)->exists()
        );
    }

    public function test_inactive_member_cannot_access_portal(): void
    {
        $member = Member::factory()->create(['is_active' => false]);
        $panel = filament()->getPanel('user');

        $this->assertFalse($member->canAccessPanel($panel));
    }

    public function test_cashier_cannot_access_admin_panel(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::Cashier]);
        $panel = filament()->getPanel('admin');

        $this->assertFalse($cashier->canAccessPanel($panel));
    }

    public function test_collection_cashier_login_redirects_to_change_password(): void
    {
        $this->assertTrue(Route::has('filament.cashier.pages.change-password'));

        $cashier = User::factory()->create([
            'role' => UserRole::CollectionCashier,
            'must_change_password' => true,
        ]);

        $this->actingAs($cashier);
        filament()->setCurrentPanel(filament()->getPanel('cashier'));

        $response = app(FilamentLoginResponse::class)->toResponse(request());

        $this->assertTrue($response->isRedirect());
        $this->assertStringContainsString('/cashier/change-password', $response->getTargetUrl());

        $this->get('/cashier/change-password')->assertOk();
    }

    /**
     * @return array<string, mixed>
     */
    private function memberPayload(): array
    {
        return [
            'name' => 'New Member',
            'email' => 'verify-member@example.com',
            'sex' => 'female',
            'civil_status' => 'single',
            'address' => 'Temuco',
            'contact_number' => '09170000003',
            'occupation' => 'Teacher',
            'employer_department' => 'DepEd',
            'password' => 'Password12',
        ];
    }
}
