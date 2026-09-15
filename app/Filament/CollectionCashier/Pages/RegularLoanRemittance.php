<?php

namespace App\Filament\CollectionCashier\Pages;

use App\Enums\LoanStatus;
use App\Models\RegularLoan;
use App\Support\CharacterLoanLedgerEntries;
use App\Support\PesoInput;
use App\Support\RecordMemberLoanPayment;
use App\Support\RegularLoanSchedule;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class RegularLoanRemittance extends Page
{
    protected static ?string $navigationLabel = 'Regular loan';

    protected static ?string $title = 'Regular loan';

    protected static ?string $slug = 'regular-loan';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.collection-cashier.regular-loan-remittance';

    public string $memberSearch = '';

    /**
     * @var list<int>
     */
    public array $hiddenLoanIds = [];

    /**
     * @var array<int|string, string>
     */
    public array $amounts = [];

    /**
     * @var array<int|string, string>
     */
    public array $rowErrors = [];

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Regular loan');
    }

    public function hideLoan(int $loanId): void
    {
        $this->hiddenLoanIds = array_values(array_unique([
            ...$this->normalizedHiddenLoanIds(),
            $loanId,
        ]));

        unset($this->amounts[$loanId], $this->rowErrors[$loanId]);
    }

    public function restoreHidden(): void
    {
        $this->hiddenLoanIds = [];
    }

    /**
     * @return Collection<int, array{
     *     loan: RegularLoan,
     *     remaining: float,
     *     installment: float
     * }>
     */
    public function getOpenLoans(): Collection
    {
        $hidden = $this->normalizedHiddenLoanIds();
        $term = trim($this->memberSearch);
        $lower = strtolower($term);

        return RegularLoan::query()
            ->where('status', LoanStatus::Approved)
            ->with(['member', 'payments'])
            ->get()
            ->map(function (RegularLoan $loan): ?array {
                $remaining = RecordMemberLoanPayment::remainingPrincipal($loan);

                if ($remaining <= RecordMemberLoanPayment::EPSILON) {
                    return null;
                }

                return [
                    'loan' => $loan,
                    'remaining' => $remaining,
                    'installment' => $this->installmentHint($loan),
                ];
            })
            ->filter()
            ->reject(fn (array $row): bool => in_array((int) $row['loan']->id, $hidden, true))
            ->filter(function (array $row) use ($term, $lower): bool {
                if ($term === '') {
                    return true;
                }

                $member = $row['loan']->member;

                if ($member === null) {
                    return ctype_digit($term) && (int) $term === (int) $row['loan']->id;
                }

                return str_contains(strtolower((string) $member->name), $lower)
                    || str_contains(strtolower((string) $member->email), $lower)
                    || ($member->contact_number && str_contains((string) $member->contact_number, $term))
                    || (ctype_digit($term) && (
                        (int) $term === (int) $member->id
                        || (int) $term === (int) $row['loan']->id
                    ));
            })
            ->sortBy(fn (array $row): string => strtolower((string) ($row['loan']->member?->name ?? '')))
            ->values();
    }

    public function hiddenCount(): int
    {
        return count($this->hiddenLoanIds);
    }

    public function recordRemittance(int $loanId): void
    {
        unset($this->rowErrors[$loanId]);

        $loan = RegularLoan::query()
            ->with(['member', 'payments'])
            ->find($loanId);

        if (! $loan instanceof RegularLoan) {
            $this->rowErrors[$loanId] = __('This loan could not be found.');

            return;
        }

        if (in_array($loanId, $this->normalizedHiddenLoanIds(), true)) {
            $this->rowErrors[$loanId] = __('This account is hidden from the Landbank list.');

            return;
        }

        $amount = PesoInput::parse($this->amounts[$loanId] ?? '');

        try {
            RecordMemberLoanPayment::apply(
                $loan,
                $amount,
                CharacterLoanLedgerEntries::KIND_PRINCIPAL,
                null,
                now(),
            );
        } catch (InvalidArgumentException $exception) {
            $this->rowErrors[$loanId] = $exception->getMessage();

            return;
        }

        unset($this->amounts[$loanId], $this->rowErrors[$loanId]);

        Notification::make()
            ->title(__('Payment recorded'))
            ->body(__('₱:amount applied to :member regular loan.', [
                'amount' => number_format($amount, 2),
                'member' => $loan->member?->name ?? '#'.$loan->member_id,
            ]))
            ->success()
            ->send();
    }

    /**
     * @return list<int>
     */
    protected function normalizedHiddenLoanIds(): array
    {
        return array_values(array_unique(array_map(intval(...), $this->hiddenLoanIds)));
    }

    protected function installmentHint(RegularLoan $loan): float
    {
        $stored = round((float) $loan->installment_amount, 2);

        if ($stored > RecordMemberLoanPayment::EPSILON) {
            return $stored;
        }

        return round(RegularLoanSchedule::fromLoan($loan)->monthlyInstallment, 2);
    }
}
