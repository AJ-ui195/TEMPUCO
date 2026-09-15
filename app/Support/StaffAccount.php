<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Validator;

final class StaffAccount
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function create(array $data): User
    {
        $password = $data['password'] ?? null;

        Validator::make(
            ['password' => $password],
            ['password' => ['required', 'string', PasswordRules::rule()]],
        )->validate();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'email_verified_at' => now(),
            'password' => $password,
            'created_by' => MemberAccount::creatorName(),
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
            'must_change_password' => true,
        ]);

        AuditLog::record('staff.created', $user, [
            'role' => $user->role?->value,
            'email' => $user->email,
        ]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function update(User $user, array $data): User
    {
        $wasActive = $user->isActive();
        $updates = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
        ];

        if (array_key_exists('is_active', $data)) {
            $updates['is_active'] = (bool) $data['is_active'];
        }

        if (filled($data['password'] ?? null)) {
            Validator::make(
                ['password' => $data['password']],
                ['password' => ['required', 'string', PasswordRules::rule()]],
            )->validate();

            $updates['password'] = $data['password'];
            $updates['must_change_password'] = true;
        }

        $user->update($updates);

        if ($wasActive && array_key_exists('is_active', $data) && ! $data['is_active']) {
            AuditLog::record('staff.disabled', $user);
        }

        if (filled($data['password'] ?? null)) {
            AuditLog::record('password.reset_by_admin', $user);
        }

        return $user->refresh();
    }
}
