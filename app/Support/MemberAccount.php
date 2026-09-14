<?php

namespace App\Support;

use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MemberAccount
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function create(array $data): Member
    {
        return DB::transaction(function () use ($data): Member {
            $password = $data['password'] ?? null;
            if (! filled($password)) {
                throw ValidationException::withMessages([
                    'password' => __('Password is required.'),
                ]);
            }

            return Member::query()->create([
                'created_by' => self::creator()?->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'email_verified_at' => now(),
                'password' => $password,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'sex' => $data['sex'] ?? null,
                'civil_status' => $data['civil_status'] ?? null,
                'address' => $data['address'] ?? null,
                'contact_number' => $data['contact_number'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'employer_department' => $data['employer_department'] ?? null,
                'is_retiree' => (bool) ($data['is_retiree'] ?? false),
                'points' => (int) ($data['points'] ?? 0),
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function update(Member $member, array $data): Member
    {
        return DB::transaction(function () use ($member, $data): Member {
            $memberUpdates = [
                'name' => $data['name'],
                'email' => $data['email'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'sex' => $data['sex'] ?? null,
                'civil_status' => $data['civil_status'] ?? null,
                'address' => $data['address'] ?? null,
                'contact_number' => $data['contact_number'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'employer_department' => $data['employer_department'] ?? null,
                'is_retiree' => (bool) ($data['is_retiree'] ?? false),
            ];

            if (filled($data['password'] ?? null)) {
                $memberUpdates['password'] = $data['password'];
            }

            $member->update($memberUpdates);

            return $member->refresh();
        });
    }

    public static function delete(Member $member): void
    {
        $member->delete();
    }

    public static function creator(): ?User
    {
        $creator = auth()->user();

        return $creator instanceof User ? $creator : null;
    }

    public static function creatorName(): string
    {
        $creator = self::creator();

        if ($creator && filled($creator->name)) {
            return (string) $creator->name;
        }

        return 'System';
    }
}
