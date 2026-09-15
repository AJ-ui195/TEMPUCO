<?php

namespace App\Support;

use App\Enums\LoanStatus;
use App\Models\Contracts\MemberLoan;
use App\Models\LoanPayment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class LoanDecisionService
{
    public function approve(User $admin, MemberLoan $loan, string $password, ?string $notes = null): MemberLoan
    {
        Gate::forUser($admin)->authorize('approve', $loan);
        $this->assertCurrentPassword($admin, $password);
        $this->assertPending($loan);

        $loan->forceFill([
            'status' => LoanStatus::Approved,
            'approved_at' => now(),
            'rejected_at' => null,
            'decided_by' => $admin->id,
            'decision_notes' => filled($notes) ? $notes : null,
        ])->save();

        AuditLog::record('loan.approved', $loan, [
            'notes' => $notes,
        ], $admin);

        return $loan->refresh();
    }

    public function reject(User $admin, MemberLoan $loan, string $password, string $notes): MemberLoan
    {
        Gate::forUser($admin)->authorize('reject', $loan);
        $this->assertCurrentPassword($admin, $password);
        $this->assertPending($loan);

        if (! filled($notes)) {
            throw ValidationException::withMessages([
                'decision_notes' => __('A reason is required when rejecting a loan.'),
            ]);
        }

        $loan->forceFill([
            'status' => LoanStatus::Rejected,
            'approved_at' => null,
            'rejected_at' => now(),
            'decided_by' => $admin->id,
            'decision_notes' => $notes,
        ])->save();

        AuditLog::record('loan.rejected', $loan, [
            'notes' => $notes,
        ], $admin);

        return $loan->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordPayment(User $admin, MemberLoan $loan, array $data): LoanPayment
    {
        Gate::forUser($admin)->authorize('recordPayment', $loan);

        $payment = $loan->payments()->create([
            'amount' => $data['amount'],
            'kind' => $data['kind'] ?? null,
            'official_receipt_no' => $data['official_receipt_no'] ?? null,
            'received_at' => $data['received_at'],
            'received_by' => $admin->id,
        ]);

        AuditLog::record('loan.payment_recorded', $loan, [
            'amount' => $data['amount'],
            'payment_id' => $payment->id,
        ], $admin);

        return $payment;
    }

    private function assertCurrentPassword(User $admin, string $password): void
    {
        if (! Hash::check($password, (string) $admin->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('The password is incorrect.'),
            ]);
        }
    }

    private function assertPending(MemberLoan $loan): void
    {
        if ($loan->status !== LoanStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => __('Only pending loan applications can be decided.'),
            ]);
        }
    }
}
