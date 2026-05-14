<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Loan;
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
        // User::factory(10)->create();

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

        Loan::query()->insert([
            [
                'user_id' => $member->id,
                'apply_loan' => 'Personal loan',
                'loan_amount' => 50000.00,
                'loan_period_months' => 12,
                'installment_amount' => 4500.00,
                'loan_date' => now()->subMonths(2)->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $member->id,
                'apply_loan' => 'Emergency fund',
                'loan_amount' => 15000.00,
                'loan_period_months' => 6,
                'installment_amount' => 2650.00,
                'loan_date' => now()->subWeeks(3)->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
