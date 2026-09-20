<?php

namespace App\Filament\CollectionCashier\Pages;

use App\Enums\LoanStatus;
use App\Enums\ReceiptKind;
use App\Models\CancelledReceipt;
use App\Models\LoanPayment;
use App\Models\PosCreditPayment;
use App\Models\RegularLoan;
use App\Support\CharacterLoanLedgerEntries;
use App\Support\CollectionReceiptNumbers;
use App\Support\CollectionReceipts;
use App\Support\PesoInput;
use App\Support\RecordMemberLoanPayment;
use App\Support\RegularLoanSchedule;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
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

    public string $paymentDate = '';

    public string $officialReceiptNo = '';

    public ?string $paymentError = null;

    public bool $showSavedModal = false;

    public string $savedTitle = '';

    public string $savedMessage = '';

    public string $savedTone = 'save';

    public ?int $editingLoanPaymentId = null;

    public ?int $editingLoanId = null;

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

    public function mount(): void
    {
        $this->paymentDate = now()->toDateString();
        $this->refreshReceiptNumbers();
    }

    public function refreshReceiptNumbers(): void
    {
        $this->officialReceiptNo = CollectionReceiptNumbers::nextOfficialReceiptNo();
    }

    public function updatedOfficialReceiptNo(): void
    {
        $this->paymentError = null;
        $this->tryLoadFromReceiptNumber(false);
    }

    public function searchOfficialReceipt(): void
    {
        $this->tryLoadFromReceiptNumber(true);
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

        if ($this->editingLoanId === $loanId) {
            $this->editingLoanId = null;
            $this->editingLoanPaymentId = null;
        }
    }

    public function clearList(): void
    {
        $this->selectedLoanIds = [];
        $this->amounts = [];
        $this->rowErrors = [];
        $this->editingLoanPaymentId = null;
        $this->editingLoanId = null;
    }

    /**
     * @return Collection<int, array{loan: RegularLoan, remaining: float, installment: float}>
     */
    public function getListedLoans(): Collection
    {
        $selected = $this->normalizedSelectedLoanIds();
        $open = $this->allOpenRows()->keyBy(fn (array $row): int => (int) $row['loan']->id);

        $rows = collect($selected)
            ->map(function (int $loanId) use ($open): ?array {
                if ($open->has($loanId)) {
                    return $open->get($loanId);
                }

                if ($this->editingLoanId === $loanId) {
                    $loan = RegularLoan::query()
                        ->with(['member', 'payments'])
                        ->find($loanId);

                    return $loan instanceof RegularLoan ? $this->rowForLoan($loan) : null;
                }

                return null;
            })
            ->filter()
            ->sortBy(fn (array $row): string => strtolower((string) ($row['loan']->member?->name ?? '')))
            ->values();

        return $rows;
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

    public function getTotalLandbankAmount(): float
    {
        $total = 0.0;

        foreach ($this->getListedLoans() as $row) {
            $loanId = (int) $row['loan']->id;
            $total = round($total + PesoInput::parse($this->amounts[$loanId] ?? ''), 2);
        }

        return $total;
    }

    public function hydrate(): void
    {
        foreach ($this->normalizedSelectedLoanIds() as $loanId) {
            $this->fillLandbankAmountFromInstallment($loanId);
        }
    }

    public function recordAllRemittances(): void
    {
        $this->savePayment();
    }

    public function savePayment(): void
    {
        $this->paymentError = null;
        $this->rowErrors = [];
        $this->editingLoanPaymentId = null;
        $this->editingLoanId = null;

        if (! $this->assertReceiptAvailable()) {
            return;
        }

        $listed = $this->getListedLoans();

        if ($listed->isEmpty()) {
            $this->openSavedModal(
                __('Nothing to record'),
                __('Add accounts to the APDS list first.'),
                'cancel',
            );

            return;
        }

        $recorded = 0;
        $failed = 0;
        $receiptNo = trim($this->officialReceiptNo);
        $receivedAt = filled($this->paymentDate)
            ? Carbon::parse($this->paymentDate)
            : now();

        foreach ($listed as $row) {
            $loanId = (int) $row['loan']->id;
            $this->fillLandbankAmountFromInstallment($loanId);

            while (
                $receiptNo !== ''
                && CollectionReceipts::isTaken($receiptNo, ReceiptKind::OfficialReceipt->value)
            ) {
                $receiptNo = $this->incrementReceiptNumber($receiptNo);
            }

            if ($this->recordOneRemittance($loanId, $receiptNo, $receivedAt)) {
                $recorded++;
                $receiptNo = $this->incrementReceiptNumber($receiptNo);
            } else {
                $failed++;
            }
        }

        if ($recorded === 0 && $failed === 0) {
            $this->openSavedModal(
                __('Nothing to record'),
                __('Add accounts to the APDS list first.'),
                'cancel',
            );

            return;
        }

        $this->refreshReceiptNumbers();

        if ($failed === 0) {
            $this->openSavedModal(
                __('Payment saved'),
                __(':count Landbank payment(s) recorded.', ['count' => $recorded]),
                'save',
            );

            return;
        }

        $this->officialReceiptNo = $receiptNo;
        $this->openSavedModal(
            __('Some payments were not recorded'),
            __(':recorded recorded, :failed skipped. Check the amount on each failed row.', [
                'recorded' => $recorded,
                'failed' => $failed,
            ]),
            'cancel',
        );
    }

    public function cancelOfficialReceipt(): void
    {
        $this->paymentError = null;
        $number = trim($this->officialReceiptNo);

        if ($number === '') {
            $this->paymentError = __('Enter the O.R. number.');

            return;
        }

        if (CollectionReceipts::isTaken($number, ReceiptKind::OfficialReceipt->value)) {
            $this->paymentError = __('This number is already used or cancelled.');

            return;
        }

        CancelledReceipt::query()->create([
            'number' => $number,
            'kind' => ReceiptKind::OfficialReceipt->value,
            'cancelled_by' => auth()->id(),
        ]);

        $this->refreshReceiptNumbers();
        $this->openSavedModal(
            __('Cancelled OR#'),
            __(':number was recorded as cancelled and cannot be used again.', ['number' => $number]),
            'cancel',
        );
    }

    public function deletePaymentDraft(): void
    {
        $this->paymentError = null;
        $number = trim($this->officialReceiptNo);

        if ($number === '') {
            $this->paymentError = __('Enter the O.R. number to delete.');

            return;
        }

        if (CollectionReceipts::isCancelled($number, ReceiptKind::OfficialReceipt->value)) {
            $this->paymentError = __('This number was cancelled and cannot be deleted as a payment.');

            return;
        }

        $hit = CollectionReceipts::findPosted($number, ReceiptKind::OfficialReceipt->value);
        $loanPayment = $hit['loan'];
        $canteenPayment = $hit['canteen'];

        if (! $loanPayment instanceof LoanPayment && ! $canteenPayment instanceof PosCreditPayment) {
            $this->paymentError = __('No payment found for this number.');

            return;
        }

        if ($loanPayment instanceof LoanPayment) {
            $loanPayment->delete();
        }

        if ($canteenPayment instanceof PosCreditPayment) {
            $canteenPayment->delete();
        }

        $this->editingLoanPaymentId = null;
        $this->editingLoanId = null;
        $this->refreshReceiptNumbers();
        $this->openSavedModal(
            __('Payment deleted'),
            __('The receipt :number was removed.', ['number' => $number]),
            'delete',
        );
    }

    public function updatePayment(): void
    {
        $this->paymentError = null;
        $number = trim($this->officialReceiptNo);

        if ($number === '') {
            $this->paymentError = __('Enter the O.R. number.');

            return;
        }

        if (CollectionReceipts::isCancelled($number, ReceiptKind::OfficialReceipt->value)) {
            $this->paymentError = __('This number was cancelled and cannot be updated.');

            return;
        }

        $loanPayment = CollectionReceipts::findRegularLoanPayment($number);

        if (! $loanPayment instanceof LoanPayment) {
            $this->paymentError = __('No regular loan payment found for this number.');

            return;
        }

        $alreadyLoaded = $this->editingLoanPaymentId === $loanPayment->id;

        if (! $alreadyLoaded) {
            $this->loadPostedPayment($loanPayment);

            if ($this->editingLoanPaymentId === $loanPayment->id) {
                $this->openSavedModal(
                    __('Payment loaded'),
                    __('Account information was loaded for :number. Change the amount, then click Update payment again.', [
                        'number' => $number,
                    ]),
                    'update',
                );
            }

            return;
        }

        $loanId = (int) ($this->editingLoanId ?? 0);
        $amount = PesoInput::parse($this->amounts[$loanId] ?? '');

        try {
            RecordMemberLoanPayment::revise($loanPayment, $amount);

            if (filled($this->paymentDate)) {
                $loanPayment->received_at = Carbon::parse($this->paymentDate);
                $loanPayment->save();
            }
        } catch (InvalidArgumentException $exception) {
            $this->paymentError = $exception->getMessage();
            $this->rowErrors[$loanId] = $exception->getMessage();

            return;
        }

        $this->openSavedModal(
            __('Payment updated'),
            __('Receipt :number was updated in the database.', ['number' => $number]),
            'update',
        );
    }

    public function closeSavedModal(): void
    {
        $this->showSavedModal = false;
        $this->savedTitle = '';
        $this->savedMessage = '';
        $this->savedTone = 'save';
    }

    protected function openSavedModal(string $title, string $message, string $tone = 'save'): void
    {
        $this->savedTitle = $title;
        $this->savedMessage = $message;
        $this->savedTone = $tone;
        $this->showSavedModal = true;
    }

    protected function recordOneRemittance(int $loanId, string $officialReceiptNo, mixed $receivedAt): bool
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
                $officialReceiptNo,
                $receivedAt,
                ReceiptKind::OfficialReceipt->value,
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

                return $this->rowForLoan($loan, $remaining);
            })
            ->filter()
            ->values();
    }

    /**
     * @return array{loan: RegularLoan, remaining: float, installment: float}
     */
    protected function rowForLoan(RegularLoan $loan, ?float $remaining = null): array
    {
        return [
            'loan' => $loan,
            'remaining' => $remaining ?? RecordMemberLoanPayment::remainingPrincipal($loan),
            'installment' => $this->installmentHint($loan),
        ];
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
            fn (int $id): bool => in_array($id, $openIds, true) || $id === $this->editingLoanId,
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

    protected function tryLoadFromReceiptNumber(bool $notifyMissing): void
    {
        $number = trim($this->officialReceiptNo);

        if ($number === '') {
            return;
        }

        if (CollectionReceipts::isCancelled($number, ReceiptKind::OfficialReceipt->value)) {
            $this->editingLoanPaymentId = null;
            $this->editingLoanId = null;

            if ($notifyMissing) {
                $this->paymentError = __('This number was cancelled and cannot be updated.');
            }

            return;
        }

        $loanPayment = CollectionReceipts::findRegularLoanPayment($number);

        if (! $loanPayment instanceof LoanPayment) {
            $this->editingLoanPaymentId = null;
            $this->editingLoanId = null;

            if ($notifyMissing && ! $this->isNextUnusedReceipt($number)) {
                $this->paymentError = __('No payment found for this number.');
            }

            return;
        }

        $this->loadPostedPayment($loanPayment);
    }

    protected function loadPostedPayment(LoanPayment $loanPayment): void
    {
        $this->paymentError = null;
        $loanPayment->load(['characterLoan.member', 'quickLoan.member', 'regularLoan.member']);
        $loan = $loanPayment->loan();

        if (! $loan instanceof RegularLoan) {
            $this->paymentError = __('This O.R. # is not a regular loan Landbank payment.');
            $this->editingLoanPaymentId = null;
            $this->editingLoanId = null;

            return;
        }

        $this->editingLoanPaymentId = $loanPayment->id;
        $this->editingLoanId = $loan->id;
        $this->memberSearch = '';
        $this->selectedLoanIds = [$loan->id];
        $this->amounts = [
            $loan->id => number_format((float) $loanPayment->amount, 2, '.', ','),
        ];
        unset($this->rowErrors[$loan->id]);

        if ($loanPayment->received_at) {
            $this->paymentDate = $loanPayment->received_at->toDateString();
        }
    }

    protected function assertReceiptAvailable(): bool
    {
        $number = trim($this->officialReceiptNo);

        if ($number === '') {
            $this->paymentError = __('Enter the O.R. number.');

            return false;
        }

        if (CollectionReceipts::isTaken($number, ReceiptKind::OfficialReceipt->value)) {
            $this->paymentError = __('This receipt number is already used or cancelled.');

            return false;
        }

        return true;
    }

    protected function incrementReceiptNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if ($digits === '') {
            return $number;
        }

        $width = max(6, strlen($digits));

        return str_pad((string) ((int) $digits + 1), $width, '0', STR_PAD_LEFT);
    }

    protected function isNextUnusedReceipt(string $number): bool
    {
        $typed = preg_replace('/\D+/', '', trim($number)) ?? '';
        $next = preg_replace('/\D+/', '', CollectionReceiptNumbers::nextOfficialReceiptNo()) ?? '';

        if ($typed === '' || $next === '') {
            return false;
        }

        return str_pad($typed, 6, '0', STR_PAD_LEFT) === str_pad($next, 6, '0', STR_PAD_LEFT);
    }
}
