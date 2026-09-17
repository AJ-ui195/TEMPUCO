<?php

namespace Tests\Feature;

use App\Enums\LoanCategory;
use App\Enums\LoanPurpose;
use App\Enums\LoanStatus;
use App\Enums\ModeOfPayment;
use App\Enums\UserRole;
use App\Filament\User\Pages\ApplyLoan;
use App\Models\CharacterLoan;
use App\Models\Member;
use App\Models\RegularLoan;
use App\Models\User;
use App\Notifications\VerifyLoanApplication;
use App\Support\LoanApplicationService;
use App\Support\LoanDecisionService;
use App\Support\LoanTypes;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LoanSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_loan_application_page(): void
    {
        $this->get('/portal/apply-loan')->assertRedirect();
    }

    public function test_cashier_cannot_open_admin_regular_loans(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::Cashier]);

        $this->actingAs($cashier)
            ->get('/admin/regular-loans')
            ->assertForbidden();
    }

    public function test_member_cannot_open_admin_regular_loans(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($member, 'member')
            ->get('/admin/regular-loans')
            ->assertRedirect();
    }

    public function test_inactive_member_cannot_access_apply_loan_page(): void
    {
        $member = Member::factory()->create(['is_active' => false]);

        $this->assertFalse(ApplyLoan::canAccess());

        $this->actingAs($member, 'member');

        $this->assertFalse(ApplyLoan::canAccess());
        $this->assertFalse($member->can('create', RegularLoan::class));
        $this->assertFalse($member->can('create', CharacterLoan::class));
    }

    public function test_member_cannot_spoof_owner_or_approved_status(): void
    {
        Notification::fake();

        $member = Member::factory()->create();
        $other = Member::factory()->create();

        $loan = app(LoanApplicationService::class)->submit($member, $this->characterLoanPayload([
            'member_id' => $other->id,
            'status' => LoanStatus::Approved->value,
            'approved_at' => now()->toDateTimeString(),
        ]));

        $this->assertInstanceOf(CharacterLoan::class, $loan);
        $this->assertSame($member->id, $loan->member_id);
        $this->assertSame(LoanStatus::AwaitingVerification, $loan->status);
        $this->assertNull($loan->approved_at);
        $this->assertNull($loan->email_verified_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'loan.submitted',
            'subject_id' => $loan->id,
        ]);
    }

    public function test_mass_assignment_cannot_set_status_or_owner(): void
    {
        $loan = new RegularLoan;
        $loan->fill([
            'member_id' => 999,
            'status' => LoanStatus::Approved->value,
            'approved_at' => now(),
            'loan_type' => 'Personal loan',
            'loan_amount' => 1000,
            'loan_period_months' => 1,
            'installment_amount' => 1000,
            'loan_date' => now()->toDateString(),
        ]);

        $this->assertNull($loan->member_id);
        $this->assertNull($loan->status);
        $this->assertNull($loan->approved_at);
    }

    public function test_quick_loan_amount_cannot_exceed_server_cap(): void
    {
        $member = Member::factory()->create();

        $this->expectException(ValidationException::class);

        app(LoanApplicationService::class)->submit($member, [
            'loan_application_type' => 'quick',
            'applicant_name' => $member->name,
            'applicant_address' => $member->address,
            'loan_amount' => 5000,
            'loan_period_months' => 1,
            'installment_amount' => 5050,
            'first_payment_due_date' => now()->addMonth()->toDateString(),
            'applicant_signed_at' => now()->toDateString(),
            'purpose_of_loan' => LoanPurpose::Personal->value,
            'mode_of_payment' => ModeOfPayment::CashPayment->value,
            'loan_type' => LoanTypes::QUICK,
        ]);
    }

    public function test_member_cannot_submit_a_second_pending_application(): void
    {
        Notification::fake();

        $member = Member::factory()->create();
        app(LoanApplicationService::class)->submit($member, $this->characterLoanPayload());

        $this->expectException(ValidationException::class);

        app(LoanApplicationService::class)->submit($member, $this->characterLoanPayload());
    }

    public function test_cashier_cannot_approve_a_loan(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::Cashier]);
        $loan = RegularLoan::factory()->pending()->create();

        $this->expectException(AuthorizationException::class);

        app(LoanDecisionService::class)->approve($cashier, $loan, 'password');
    }

    public function test_loan_application_requires_email_confirmation_before_admin_can_approve(): void
    {
        Notification::fake();

        $member = Member::factory()->create();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $loan = app(LoanApplicationService::class)->submit($member, $this->characterLoanPayload());

        $this->assertSame(LoanStatus::AwaitingVerification, $loan->status);
        Notification::assertSentTo($member, VerifyLoanApplication::class);

        $this->expectException(AuthorizationException::class);
        app(LoanDecisionService::class)->approve($admin, $loan, 'password');
    }

    public function test_email_confirmation_submits_loan_for_admin_review(): void
    {
        Notification::fake();

        $member = Member::factory()->create();
        $loan = app(LoanApplicationService::class)->submit($member, $this->characterLoanPayload());
        $url = null;

        Notification::assertSentTo(
            $member,
            VerifyLoanApplication::class,
            function (VerifyLoanApplication $notification) use (&$url): bool {
                $url = $notification->verificationUrl;

                return true;
            },
        );

        $this->assertIsString($url);
        $this->assertStringContainsString('/loans/character/', $url);
        $this->get($url)->assertOk()->assertSee('Application confirmed', false);

        $loan->refresh();
        $this->assertSame(LoanStatus::Pending, $loan->status);
        $this->assertNotNull($loan->email_verified_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'loan.email_confirmed',
            'subject_id' => $loan->id,
        ]);
    }

    public function test_unsigned_confirmation_link_is_rejected(): void
    {
        $loan = RegularLoan::factory()->awaitingVerification()->create();

        $this->get(route('loans.verify-email', [
            'type' => 'regular',
            'loan' => $loan,
            'token' => 'test-token',
        ]))->assertForbidden();
    }

    public function test_admin_can_approve_pending_loan_and_writes_audit(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $loan = RegularLoan::factory()->pending()->create();

        $result = app(LoanDecisionService::class)->approve($admin, $loan, 'password', 'Committee approved');

        $this->assertSame(LoanStatus::Approved, $result->status);
        $this->assertSame($admin->id, $result->decided_by);
        $this->assertNotNull($result->approved_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'loan.approved',
            'subject_id' => $loan->id,
            'actor_id' => $admin->id,
        ]);
    }

    public function test_admin_cannot_approve_an_already_decided_loan(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $loan = RegularLoan::factory()->approved()->create();

        $this->expectException(AuthorizationException::class);

        app(LoanDecisionService::class)->approve($admin, $loan, 'password');
    }

    public function test_admin_reject_requires_notes_and_locks_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $loan = RegularLoan::factory()->pending()->create();

        $result = app(LoanDecisionService::class)->reject($admin, $loan, 'password', 'Incomplete documents');

        $this->assertSame(LoanStatus::Rejected, $result->status);
        $this->assertSame('Incomplete documents', $result->decision_notes);
        $this->assertNotNull($result->rejected_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'loan.rejected',
            'subject_id' => $loan->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function characterLoanPayload(array $overrides = []): array
    {
        return array_merge([
            'loan_application_type' => 'character',
            'applicant_name' => 'Test Member',
            'applicant_address' => 'Temuco',
            'loan_amount' => 10000,
            'loan_period_months' => 3,
            'installment_amount' => 600,
            'first_payment_due_date' => now()->addMonth()->toDateString(),
            'applicant_signed_at' => now()->toDateString(),
            'purpose_of_loan' => LoanPurpose::Personal->value,
            'mode_of_payment' => ModeOfPayment::CashPayment->value,
            'loan_category' => LoanCategory::AdditionalNew->value,
            'character_loan_type' => LoanTypes::CHARACTER,
            'loan_type' => LoanTypes::CHARACTER,
        ], $overrides);
    }
}
