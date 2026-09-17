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
    public array $selectedLoanIds = [];

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

    public function addLoan(int $loanId): void
    {
        if (! $this->openRowById($loanId)) {
            Notification::make()
                ->title(__('Account not added'))
                ->body(__('This regular loan is not open or could not be found.'))
                ->danger()
                ->send();

            return;
        }

        $this->selectedLoanIds = array_values(array_unique([
            ...$this->normalizedSelectedLoanIds(),
            $loanId,
        ]));

        $this->fillLandbankAmountFromInstallment($loanId);
    }

    public function addAll(): void
    {
        $this->selectedLoanIds = $this->allOpenRows()
            ->map(fn (array $row): int => (int) $row['loan']->id)
            ->all();

        foreach ($this->selectedLoanIds as $loanId) {
            $this->fillLandbankAmountFromInstallment($loanId);
        }
    }

    public function removeLoan(int $loanId): void
    {
        $this->selectedLoanIds = array_values(array_filter(
            $this->normalizedSelectedLoanIds(),
            fn (int $id): bool => $id !== $loanId,
        ));

        unset($this->amounts[$loanId], $this->rowErrors[$loanId]);
    }

    public function clearList(): void
    {
        $this->selectedLoanIds = [];
        $this->amounts = [];
        $this->rowErrors = [];
    }

    /**
     * @return Collection<int, array{loan: RegularLoan, remaining: float, installment: float}>
     */
    public function getListedLoans(): Collection
    {
        $selected = $this->normalizedSelectedLoanIds();

        return $this->allOpenRows()
            ->filter(fn (array $row): bool => in_array((int) $row['loan']->id, $selected, true))
            ->sortBy(fn (array $row): string => strtolower((string) ($row['loan']->member?->name ?? '')))
            ->values();
    }

    /**
     * @return Collection<int, array{loan: RegularLoan, remaining: float, installment: float}>
     */
    public function getAddableLoans(): Collection
    {
        $selected = $this->normalizedSelectedLoanIds();
        $term = trim($this->memberSearch);

        $rows = $this->allOpenRows()
            ->reject(fn (array $row): bool => in_array((int) $row['loan']->id, $selected, true));

        if ($term === '') {
            return $rows
                ->sortBy(fn (array $row): string => strtolower((string) ($row['loan']->member?->name ?? '')))
                ->values();
        }

        return $rows
            ->filter(fn (array $row): bool => $this->matchesSearch($row, $term))
            ->sortBy(fn (array $row): string => strtolower((string) ($row['loan']->member?->name ?? '')))
            ->values();
    }

    public function listedCount(): int
    {
        return $this->getListedLoans()->count();
    }

    public function addableCount(): int
    {
        return $this->allOpenRows()->count() - $this->listedCount();
    }

    public function hydrate(): void
    {
        foreach ($this->normalizedSelectedLoanIds() as $loanId) {
            $this->fillLandbankAmountFromInstallment($loanId);
        }
    }

    public function recordAllRemittances(): void
    {
        $this->rowErrors = [];

        $recorded = 0;
        $failed = 0;

        foreach ($this->getListedLoans() as $row) {
            $loanId = (int) $row['loan']->id;
            $this->fillLandbankAmountFromInstallment($loanId);

            if ($this->recordOneRemittance($loanId)) {
                $recorded++;
            } else {
                $failed++;
            }
        }

        if ($recorded === 0 && $failed === 0) {
            Notification::make()
                ->title(__('Nothing to record'))
                ->body(__('Add accounts to the APDS list first.'))
                ->warning()
                ->send();

            return;
        }

        if ($failed === 0) {
            Notification::make()
                ->title(__('Payments recorded'))
                ->body(__(':count Landbank payment(s) recorded.', ['count' => $recorded]))
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title(__('Some payments were not recorded'))
            ->body(__(':recorded recorded, :failed skipped. Check the amount on each failed row.', [
                'recorded' => $recorded,
                'failed' => $failed,
            ]))
            ->danger()
            ->send();
    }

    protected function recordOneRemittance(int $loanId): bool
    {
        unset($this->rowErrors[$loanId]);

        $loan = RegularLoan::query()
            ->with(['member', 'payments'])
            ->find($loanId);

        if (! $loan instanceof RegularLoan) {
            $this->rowErrors[$loanId] = __('This loan could not be found.');

            return false;
        }

        if (! in_array($loanId, $this->normalizedSelectedLoanIds(), true)) {
            $this->rowErrors[$loanId] = __('Add this account to the APDS list first.');

            return false;
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

            return false;
        }

        unset($this->amounts[$loanId], $this->rowErrors[$loanId]);

        $remaining = RecordMemberLoanPayment::remainingPrincipal($loan->fresh(['payments']));

        if ($remaining <= RecordMemberLoanPayment::EPSILON) {
            $this->removeLoan($loanId);
        }

        return true;
    }

    /**
     * @return Collection<int, array{loan: RegularLoan, remaining: float, installment: float}>
     */
    protected function allOpenRows(): Collection
    {
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
            ->values();
    }

    /**
     * @param  array{loan: RegularLoan, remaining: float, installment: float}  $row
     */
    protected function matchesSearch(array $row, string $term): bool
    {
        $lower = strtolower($term);
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
    }

    protected function openRowById(int $loanId): ?array
    {
        return $this->allOpenRows()
            ->first(fn (array $row): bool => (int) $row['loan']->id === $loanId);
    }

    /**
     * @return list<int>
     */
    protected function normalizedSelectedLoanIds(): array
    {
        $openIds = $this->allOpenRows()
            ->map(fn (array $row): int => (int) $row['loan']->id)
            ->all();

        return array_values(array_unique(array_filter(
            array_map(intval(...), $this->selectedLoanIds),
            fn (int $id): bool => in_array($id, $openIds, true),
        )));
    }

    protected function installmentHint(RegularLoan $loan): float
    {
        $stored = round((float) $loan->installment_amount, 2);

        if ($stored > RecordMemberLoanPayment::EPSILON) {
            return $stored;
        }

        return round(RegularLoanSchedule::fromLoan($loan)->monthlyInstallment, 2);
    }

    protected function fillLandbankAmountFromInstallment(int $loanId): void
    {
        if (filled($this->amounts[$loanId] ?? null)) {
            return;
        }

        $row = $this->openRowById($loanId);

        if ($row === null) {
            return;
        }

        $this->amounts[$loanId] = PesoInput::format((float) $row['installment']);
    }
}
