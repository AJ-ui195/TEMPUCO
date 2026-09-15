<?php

namespace Database\Seeders;

use App\Enums\LoanCategory;
use App\Enums\LoanPurpose;
use App\Enums\LoanStatus;
use App\Enums\ModeOfPayment;
use App\Enums\UserRole;
use App\Models\Loan;
use App\Models\User;
use App\Support\MemberAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => UserRole::Admin,
        ]);

        $member = MemberAccount::create([
            'name' => 'Member User',
            'email' => 'member@example.com',
            'date_of_birth' => '1990-01-15',
            'sex' => 'female',
            'civil_status' => 'married',
            'address' => 'Temuco, Philippines',
            'contact_number' => '09171234567',
            'occupation' => 'Teacher',
            'employer_department' => 'DepEd',
            'is_retiree' => false,
            'password' => 'Password12',
            'must_change_password' => false,
            'email_verified' => true,
        ]);

        User::factory()->create([
            'name' => 'Grocery Cashier',
            'email' => 'cashier@example.com',
            'role' => UserRole::Cashier,
        ]);

        User::factory()->create([
            'name' => 'Canteen Cashier',
            'email' => 'canteen@example.com',
            'role' => UserRole::CanteenCashier,
        ]);

        User::factory()->create([
            'name' => 'Inventory User',
            'email' => 'inventory@example.com',
            'role' => UserRole::Inventory,
        ]);

        $approved = new Loan;
        $approved->forceFill([
            'user_id' => $member->id,
            'status' => LoanStatus::Approved,
            'loan_category' => LoanCategory::AdditionalNew,
            'loan_type' => 'Personal loan',
            'loan_amount' => 50000.00,
            'loan_period_months' => 12,
            'installment_amount' => 4500.00,
            'first_payment_due_date' => now()->subMonths(1)->toDateString(),
            'purpose_of_loan' => LoanPurpose::Personal,
            'mode_of_payment' => ModeOfPayment::CashPayment,
            'applicant_signed_at' => now()->subMonths(2)->toDateString(),
            'loan_date' => now()->subMonths(2)->toDateString(),
            'approved_at' => now()->subMonths(2),
        ])->save();

        $pending = new Loan;
        $pending->forceFill([
            'user_id' => $member->id,
            'status' => LoanStatus::Pending,
            'loan_category' => LoanCategory::AdditionalNew,
            'loan_type' => 'Emergency fund',
            'loan_amount' => 15000.00,
            'loan_period_months' => 6,
            'installment_amount' => 2650.00,
            'first_payment_due_date' => now()->addWeeks(1)->toDateString(),
            'purpose_of_loan' => LoanPurpose::Emergency,
            'mode_of_payment' => ModeOfPayment::OverTheCounter,
            'applicant_signed_at' => now()->subWeeks(3)->toDateString(),
            'loan_date' => now()->subWeeks(3)->toDateString(),
        ])->save();
    }
}
