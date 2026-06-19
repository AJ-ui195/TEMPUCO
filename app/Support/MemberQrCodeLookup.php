<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;

final class MemberQrCodeLookup
{
    public static function resolveFromScan(string $raw): ?User
    {
        $payload = self::parsePayload($raw);

        if ($payload === null) {
            return null;
        }

        $memberId = (int) $payload['member_id'];

        if ($memberId < 1) {
            return null;
        }

        return User::query()
            ->whereKey($memberId)
            ->where('role', UserRole::User)
            ->first();
    }

    /**
     * @return array{member_id: int, name: string, email: string}|null
     */
    public static function parsePayload(string $raw): ?array
    {
        $raw = self::normalizeScan($raw);

        if ($raw === '' || ! str_contains($raw, '{')) {
            return null;
        }

        try {
            $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($decoded) || ! isset($decoded['member_id'])) {
            return null;
        }

        return [
            'member_id' => (int) $decoded['member_id'],
            'name' => (string) ($decoded['name'] ?? ''),
            'email' => (string) ($decoded['email'] ?? ''),
        ];
    }

    private static function normalizeScan(string $raw): string
    {
        $raw = trim($raw);
        $raw = ltrim($raw, "\xEF\xBB\xBF");

        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');

        if ($start !== false && $end !== false && $end >= $start) {
            $raw = substr($raw, $start, $end - $start + 1);
        }

        return trim($raw);
    }
}
