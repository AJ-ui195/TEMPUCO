<?php

namespace App\Support;

use App\Enums\PosSaleChannel;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class PrintMemberLedger
{
    /**
     * @param  User|null  $member  Null prints every member with activity.
     */
    public static function url(
        ?User $member = null,
        ?PosSaleChannel $channel = null,
        ?string $from = null,
        ?string $to = null,
        bool $autoPrint = false,
    ): string {
        return route('pos.member-ledger.print', array_filter([
            'member' => $member?->id,
            'channel' => $channel?->value,
            'from' => $from ?: null,
            'to' => $to ?: null,
            'auto' => $autoPrint ? 1 : null,
        ]));
    }

    /**
     * @return array{
     *     ledgers: Collection<int, array{member: User, entries: Collection<int, array<string, mixed>>, summary: array<string, float|int>}>,
     *     channelLabel: string,
     *     rangeLabel: string,
     *     printedAt: string,
     *     grandBalance: float,
     *     autoPrint: bool
     * }
     */
    public static function viewData(
        ?User $member,
        ?PosSaleChannel $channel,
        ?Carbon $from,
        ?Carbon $to,
        bool $autoPrint = false,
    ): array {
        $members = $member instanceof User
            ? collect([$member])
            : MemberCreditLedger::membersWithActivity($channel, $from, $to);

        $ledgers = $members->map(function (User $each) use ($channel, $from, $to): array {
            $ledger = new MemberCreditLedger($each);

            return [
                'member' => $each,
                'entries' => $ledger->entries($channel, $from, $to),
                'summary' => $ledger->summary($channel, $from, $to),
            ];
        })->values();

        return [
            'ledgers' => $ledgers,
            'channelLabel' => $channel?->getLabel() ?? __('All channels'),
            'rangeLabel' => self::rangeLabel($from, $to),
            'printedAt' => PhilippineTime::format(PhilippineTime::now()),
            'grandBalance' => round((float) $ledgers->sum(fn (array $row): float => $row['summary']['balance']), 2),
            'autoPrint' => $autoPrint,
        ];
    }

    private static function rangeLabel(?Carbon $from, ?Carbon $to): string
    {
        if ($from && $to) {
            return $from->format('M j, Y').' — '.$to->format('M j, Y');
        }

        if ($from) {
            return __('From :date', ['date' => $from->format('M j, Y')]);
        }

        if ($to) {
            return __('Up to :date', ['date' => $to->format('M j, Y')]);
        }

        return __('All dates');
    }
}
