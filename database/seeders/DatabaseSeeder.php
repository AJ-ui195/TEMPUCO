<?php

namespace Database\Seeders;

use App\Enums\LoanStatus;
use App\Enums\UserRole;
use App\Models\Loan;
use App\Models\LoanPayment;
use App\Models\User;
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

        $member = User::factory()->create([
            'name' => 'Member User',
            'email' => 'member@example.com',
            'role' => UserRole::User,
        ]);

        $approvedLoan = Loan::query()->create([
            'user_id' => $member->id,
            'status' => LoanStatus::Approved,
            'apply_loan' => 'Personal loan',
            'loan_amount' => 50000.00,
            'loan_period_months' => 12,
            'installment_amount' => 4500.00,
            'loan_date' => now()->toDateString(),
            'approved_at' => now(),
        ]);

        Loan::query()->create([
            'user_id' => $member->id,
            'status' => LoanStatus::Pending,
            'apply_loan' => 'Emergency fund',
            'loan_amount' => 15000.00,
            'loan_period_months' => 6,
            'installment_amount' => 2650.00,
            'loan_date' => now()->toDateString(),
        ]);

        LoanPayment::query()->create([
            'loan_id' => $approvedLoan->id,
            'amount' => 4500.00,
            'received_at' => now(),
        ]);
    }
}
