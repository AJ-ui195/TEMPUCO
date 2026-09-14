<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Ledger row order is when the row was stored, never O.R. / reference number.
 */
final class LedgerChronology
{
    /**
     * @param  Collection<int, mixed>  $items
     * @param  callable(mixed): mixed  $storedAt
     * @param  callable(mixed): int  $storedId
     * @return Collection<int, mixed>
     */
    public static function sortByStoredTime(Collection $items, callable $storedAt, callable $storedId): Collection
    {
        return $items
            ->sort(function (mixed $left, mixed $right) use ($storedAt, $storedId): int {
                $compared = self::timestamp($storedAt($left)) <=> self::timestamp($storedAt($right));

                if ($compared !== 0) {
                    return $compared;
                }

                return ((int) $storedId($left)) <=> ((int) $storedId($right));
            })
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return Collection<int, array<string, mixed>>
     */
    public static function sortEntries(Collection $entries): Collection
    {
        return self::sortByStoredTime(
            $entries,
            fn (array $entry) => $entry['stored_at'] ?? $entry['date'] ?? null,
            fn (array $entry): int => (int) ($entry['stored_id'] ?? 0),
        );
    }

    public static function timestamp(mixed $date): int
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->getTimestamp();
        }

        if (is_string($date) && $date !== '') {
            return Carbon::parse($date)->getTimestamp();
        }

        return 0;
    }
}
