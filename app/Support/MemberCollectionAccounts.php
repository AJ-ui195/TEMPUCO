<?php

namespace App\Support;

use App\Enums\LoanStatus;
use App\Models\CharacterLoan;
use App\Models\Contracts\MemberLoan;
use App\Models\LoanPayment;
use App\Models\Member;
use App\Models\QuickLoan;
use App\Models\RegularLoan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class MemberCollectionAccounts
{
    public const TYPE_REGULAR = 'regular';

    public const TYPE_SALARY_1 = 'salary_1';

    public const TYPE_SALARY_2 = 'salary_2';

    public const TYPE_QUICK = 'quick';

    public const TYPE_CHARACTER = 'character';

    public const TYPE_CANTEEN = 'canteen';

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_SALARY_1 => __('Salary loan 1'),
            self::TYPE_SALARY_2 => __('Salary loan 2'),
            self::TYPE_CHARACTER => __('Character loan'),
            self::TYPE_CANTEEN => __('Canteen / Grocery credit'),
            self::TYPE_QUICK => __('Quick loan'),
        ];
    }

    /**
     * @return array{
     *     ledgers: array<string, array<string, mixed>>
     * }
     */
    public static function for(Member $member): array
    {
        $loans = MemberLoans::forMember($member);
        $loans->each(fn (MemberLoan $loan) => $loan->loadMissing('payments'));
        $creditBalance = (new MemberPosCredit($member))->totalOutstanding();

        $byType = [
            self::TYPE_SALARY_1 => collect(),
            self::TYPE_SALARY_2 => collect(),
            self::TYPE_CHARACTER => collect(),
            self::TYPE_QUICK => collect(),
        ];

        foreach ($loans as $loan) {
            $type = self::accountType($loan);
            $byType[$type]->push(self::loanRow($loan));
        }

        $canteen = self::canteenRow($member, $creditBalance);
        $labels = self::typeLabels();

        return [
            'ledgers' => [
                self::TYPE_SALARY_1 => self::loanLedger(self::TYPE_SALARY_1, $labels[self::TYPE_SALARY_1], $byType[self::TYPE_SALARY_1]),
                self::TYPE_SALARY_2 => self::loanLedger(self::TYPE_SALARY_2, $labels[self::TYPE_SALARY_2], $byType[self::TYPE_SALARY_2]),
                self::TYPE_CHARACTER => self::loanLedger(self::TYPE_CHARACTER, $labels[self::TYPE_CHARACTER], $byType[self::TYPE_CHARACTER]),
                self::TYPE_CANTEEN => self::canteenLedger($labels[self::TYPE_CANTEEN], $canteen),
                self::TYPE_QUICK => self::loanLedger(self::TYPE_QUICK, $labels[self::TYPE_QUICK], $byType[self::TYPE_QUICK]),
            ],
        ];
    }

    /**
     * @return array{approved_with_balance: int, loan_outstanding: float}
     */
    public static function portfolio(): array
    {
        $count = 0;
        $outstanding = 0.0;

        foreach (MemberLoans::models() as $model) {
            $loans = $model::query()
                ->where('status', LoanStatus::Approved)
                ->with('payments')
                ->get();

            foreach ($loans as $loan) {
                $remaining = RecordMemberLoanPayment::remainingPrincipal($loan);

                if ($remaining <= RecordMemberLoanPayment::EPSILON) {
                    continue;
                }

                $count++;
                $outstanding = round($outstanding + $remaining, 2);
            }
        }

        return [
            'approved_with_balance' => $count,
            'loan_outstanding' => $outstanding,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function loanRow(MemberLoan $loan): array
    {
        $type = self::accountType($loan);
        $kind = RecordMemberLoanPayment::kindOf($loan);
        $principal = RecordMemberLoanPayment::remainingPrincipal($loan);
        $balance = $principal;
        $collectable = $loan->status === LoanStatus::Approved && $principal > RecordMemberLoanPayment::EPSILON;

        if ($loan->status === LoanStatus::Approved && $type === self::TYPE_CHARACTER) {
            if (! CharacterLoanLedgerEntries::principalUnlocked($loan)) {
                $balance = 0.0;
                $collectable = false;
            }
        }

        if ($loan->status === LoanStatus::Approved && $type === self::TYPE_QUICK && $loan instanceof QuickLoan) {
            if (! QuickLoanLedgerEntries::principalUnlocked($loan)) {
                $balance = 0.0;
                $collectable = false;
            }
        }

        return [
            'key' => $type.':'.$loan->getKey(),
            'type' => $type,
            'loan_id' => $loan->getKey(),
            'label' => self::loanLabel($loan, $kind),
            'status' => $loan->status instanceof LoanStatus ? $loan->status->getLabel() : (string) $loan->status,
            'loan_amount' => round((float) $loan->loan_amount, 2),
            'balance' => $balance,
            'collectable' => $collectable,
            'entries' => self::loanEntries($loan, $type),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function canteenRow(Member $member, float $balance): array
    {
        $entries = (new MemberCreditLedger($member))
            ->entries()
            ->map(fn (array $entry): array => [
                'date' => $entry['date'],
                'stored_at' => $entry['date'],
                'stored_id' => (int) ($entry['id'] ?? 0),
                'description' => $entry['description'],
                'reference' => $entry['reference'],
                'debit' => (float) $entry['charge'],
                'credit' => (float) $entry['payment'],
                'balance' => (float) $entry['balance'],
            ]);

        return [
            'key' => self::TYPE_CANTEEN,
            'type' => self::TYPE_CANTEEN,
            'loan_id' => null,
            'label' => __('Canteen / Grocery credit'),
            'status' => __('Open'),
            'loan_amount' => $balance,
            'balance' => $balance,
            'collectable' => $balance > RecordMemberLoanPayment::EPSILON,
            'entries' => $entries,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private static function loanLedger(string $type, string $label, Collection $rows): array
    {
        $collectable = $rows->filter(fn (array $row): bool => (bool) $row['collectable'])->values();

        return [
            'type' => $type,
            'label' => $label,
            'balance' => round((float) $collectable->sum('balance'), 2),
            'entries' => self::mergeEntries($rows),
            'accounts' => $collectable,
        ];
    }

    /**
     * @param  array<string, mixed>  $canteen
     * @return array<string, mixed>
     */
    private static function canteenLedger(string $label, array $canteen): array
    {
        $collectable = $canteen['collectable'] ? collect([$canteen]) : collect();

        return [
            'type' => self::TYPE_CANTEEN,
            'label' => $label,
            'balance' => round((float) $canteen['balance'], 2),
            'entries' => collect($canteen['entries'])->values(),
            'accounts' => $collectable,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array{date: mixed, description: string, reference: string, debit: float, credit: float, balance: float}>
     */
    private static function mergeEntries(Collection $rows): Collection
    {
        $prefixLabels = $rows->count() > 1;
        $merged = $rows->flatMap(function (array $row) use ($prefixLabels): Collection {
            return collect($row['entries'] ?? [])->map(function (array $entry) use ($row, $prefixLabels): array {
                if ($prefixLabels) {
                    $entry['description'] = $row['label'].' · '.$entry['description'];
                }

                return $entry;
            });
        });

        $sorted = LedgerChronology::sortEntries($merged);

        $running = 0.0;

        return $sorted->map(function (array $entry) use (&$running): array {
            $running = round($running + (float) $entry['debit'] - (float) $entry['credit'], 2);
            $entry['balance'] = $running;

            return $entry;
        });
    }

    /**
     * @return Collection<int, array{date: mixed, description: string, reference: string, debit: float, credit: float, balance: float}>
     */
    private static function loanEntries(MemberLoan $loan, string $type): Collection
    {
        if ($type === self::TYPE_QUICK && $loan instanceof QuickLoan) {
            return QuickLoanLedgerEntries::forLoan($loan)
                ->map(function (array $row): array {
                    $debit = (float) $row['released'] + ((float) $row['interest'] > 0 && empty($row['interest_in_parens']) ? (float) $row['interest'] : 0.0);
                    $credit = (float) $row['payment'];
                    $description = $row['released'] > 0
                        ? __('Loan released')
                        : ($row['interest'] > 0 ? __('Interest') : __('Payment'));

                    return [
                        'date' => $row['date'],
                        'stored_at' => $row['stored_at'] ?? $row['date'],
                        'stored_id' => (int) ($row['stored_id'] ?? 0),
                        'description' => $row['remarks'] !== '' ? $description.' · '.$row['remarks'] : $description,
                        'reference' => $row['or'],
                        'debit' => $debit,
                        'credit' => $credit,
                        'balance' => (float) $row['balance'],
                    ];
                });
        }

        if ($type === self::TYPE_CHARACTER) {
            return CharacterLoanLedgerEntries::forLoan($loan)
                ->map(function (array $row): array {
                    $debit = (float) $row['released'] + ((float) $row['interest'] > 0 && ! $row['interest_in_parens'] ? (float) $row['interest'] : 0.0);
                    if ($row['released'] > 0) {
                        $description = __('Loan released');
                    } elseif ((float) $row['interest'] > 0 && (float) $row['payment'] < 0.005) {
                        $description = __('Interest');
                    } else {
                        $description = __('Payment');
                    }

                    return [
                        'date' => $row['date'],
                        'stored_at' => $row['stored_at'] ?? $row['date'],
                        'stored_id' => (int) ($row['stored_id'] ?? 0),
                        'description' => $row['remarks'] !== '' ? $description.' · '.$row['remarks'] : $description,
                        'reference' => $row['or'],
                        'debit' => $debit > 0 ? $debit : (float) $row['released'],
                        'credit' => (float) $row['payment'],
                        'balance' => $row['balance_blank'] ? 0.0 : (float) $row['balance'],
                    ];
                });
        }

        $principal = round((float) $loan->loan_amount, 2);
        $release = Carbon::parse($loan->loan_date ?? $loan->approved_at ?? $loan->created_at);
        $running = $principal;
        $entries = collect([
            [
                'date' => $release,
                'stored_at' => $loan->created_at ?? $release,
                'stored_id' => 0,
                'description' => __('Loan released'),
                'reference' => '',
                'debit' => $principal,
                'credit' => 0.0,
                'balance' => $running,
            ],
        ]);

        foreach (LoanPayment::inRecordedOrder($loan->payments) as $payment) {
            $amount = round((float) $payment->amount, 2);
            $isInterest = CharacterLoanLedgerEntries::isInterestPayment($payment);

            if (! $isInterest) {
                $running = round(max(0, $running - $amount), 2);
            }

            $storedAt = $payment->created_at ?? $payment->received_at;

            $entries->push([
                'date' => $storedAt,
                'stored_at' => $storedAt,
                'stored_id' => (int) $payment->id,
                'description' => $isInterest ? __('Interest') : __('Payment'),
                'reference' => CollectionReceiptNumbers::displayOfficialReceipt((string) ($payment->official_receipt_no ?? '')),
                'debit' => $isInterest ? $amount : 0.0,
                'credit' => $isInterest ? 0.0 : $amount,
                'balance' => $running,
            ]);
        }

        return $entries->values();
    }

    public static function isSalaryLedger(string $type): bool
    {
        return in_array($type, [
            self::TYPE_REGULAR,
            self::TYPE_SALARY_1,
            self::TYPE_SALARY_2,
        ], true);
    }

    public static function ledgerType(MemberLoan $loan): string
    {
        return self::accountType($loan);
    }

    private static function accountType(MemberLoan $loan): string
    {
        if ($loan instanceof QuickLoan) {
            return self::TYPE_QUICK;
        }

        if ($loan instanceof RegularLoan) {
            return LoanTypes::isSalary2((string) $loan->loan_type)
                ? self::TYPE_SALARY_2
                : self::TYPE_SALARY_1;
        }

        if ($loan instanceof CharacterLoan || in_array(RecordMemberLoanPayment::kindOf($loan), LoanTypes::characterFamily(), true)) {
            return self::TYPE_CHARACTER;
        }

        return self::TYPE_SALARY_1;
    }

    private static function loanLabel(MemberLoan $loan, string $kind): string
    {
        $type = match (true) {
            $kind === LoanTypes::QUICK => __('Quick loan'),
            $kind === LoanTypes::CHARACTER => __('Character loan'),
            $kind === LoanTypes::CHARACTER_EMERGENCY => __('Character-Emergency loan'),
            $kind === LoanTypes::CHARACTER_SHORT_TERM => __('Character Short-Term loan'),
            $kind === LoanTypes::EMERGENCY => __('Emergency loan'),
            $kind === LoanTypes::CALAMITY => __('Calamity loan'),
            $kind === LoanTypes::RETIREE_SHORT_TERM => __('Retirees’ short-term loan'),
            $kind === LoanTypes::TRAVEL => __('Travel loan'),
            LoanTypes::isCollateralized($kind) => __('Collateralized loan'),
            $kind === LoanTypes::SALARY_2 => __('Salary loan 2'),
            $kind === LoanTypes::REGULAR => __('Salary loan 1'),
            $loan instanceof RegularLoan => __('Salary loan'),
            default => $kind !== '' ? $kind : __('Loan'),
        };

        return $type.' #'.$loan->getKey();
    }
}
