<?php

namespace App\Support;

use App\Models\CharacterLoan;
use App\Models\Contracts\MemberLoan;
use App\Models\Member;
use App\Models\QuickLoan;
use App\Models\RegularLoan;
use Illuminate\Support\Collection;

final class MemberLoans
{
    /**
     * @return list<class-string<MemberLoan>>
     */
    public static function models(): array
    {
        return [
            RegularLoan::class,
            QuickLoan::class,
            CharacterLoan::class,
        ];
    }

    /**
     * @return Collection<int, MemberLoan>
     */
    public static function forMember(Member $member): Collection
    {
        return collect(self::models())
            ->flatMap(fn (string $model) => $model::query()->forUser($member)->get())
            ->sortByDesc(fn (MemberLoan $loan): string => sprintf(
                '%s-%020d',
                ($loan->created_at ?? $loan->loan_date)?->format('Y-m-d H:i:s.u') ?? '0',
                (int) $loan->getKey(),
            ))
            ->values();
    }

    public static function countCreatedBetween(\DateTimeInterface $start, \DateTimeInterface $end): int
    {
        return collect(self::models())
            ->sum(fn (string $model) => $model::query()->whereBetween('created_at', [$start, $end])->count());
    }

    public static function sumAmountCreatedBetween(\DateTimeInterface $start, \DateTimeInterface $end): float
    {
        return (float) collect(self::models())
            ->sum(fn (string $model) => $model::query()->whereBetween('created_at', [$start, $end])->sum('loan_amount'));
    }

    public static function countApprovedBetween(\DateTimeInterface $start, \DateTimeInterface $end, mixed $status): int
    {
        return collect(self::models())
            ->sum(fn (string $model) => $model::query()
                ->where('status', $status)
                ->whereBetween('approved_at', [$start, $end])
                ->count());
    }

    public static function countOnLoanDate(\DateTimeInterface|string $date): int
    {
        return collect(self::models())
            ->sum(fn (string $model) => $model::query()->whereLoanDate($date)->count());
    }

    /**
     * @return class-string<MemberLoan>|null
     */
    public static function modelForPrintType(string $type): ?string
    {
        return match ($type) {
            'regular' => RegularLoan::class,
            'quick' => QuickLoan::class,
            'character' => CharacterLoan::class,
            default => null,
        };
    }
}
