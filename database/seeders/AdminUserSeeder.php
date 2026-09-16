<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the real admin account used for required MFA setup.
     *
     * Override with ADMIN_SEED_EMAIL / ADMIN_SEED_PASSWORD / ADMIN_SEED_NAME in .env.
     * After login: change temporary password, then confirm the email sign-in code.
     */
    public function run(): void
    {
        $email = (string) env('ADMIN_SEED_EMAIL', 'virginregodon@gmail.com');
        $name = (string) env('ADMIN_SEED_NAME', 'admin');
        $password = (string) env('ADMIN_SEED_PASSWORD', 'Password12');

        $user = User::query()->firstOrNew(['email' => $email]);

        $user->fill([
            'name' => $name,
            'role' => UserRole::Admin,
            'password' => $password,
            'is_active' => true,
            'must_change_password' => true,
            'created_by' => $user->created_by ?: 'AdminUserSeeder',
        ]);

        // These columns are not mass-assignable on User.
        $user->forceFill([
            'email_verified_at' => now(),
            'has_email_authentication' => true,
            'app_authentication_secret' => null,
            'app_authentication_recovery_codes' => null,
        ])->save();

        $this->command?->info("Admin ready: {$user->email}");
        $this->command?->warn('Sign in at /login. A 6-digit code will be emailed; this device remembers it for 1 day.');
    }
}
