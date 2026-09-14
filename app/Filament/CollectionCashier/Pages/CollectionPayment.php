<?php

namespace App\Filament\CollectionCashier\Pages;

use App\Enums\LoanStatus;
use App\Enums\PosSaleChannel;
use App\Models\CharacterLoan;
use App\Models\Contracts\MemberLoan;
use App\Models\Member;
use App\Models\PosCreditPayment;
use App\Models\QuickLoan;
use App\Models\RegularLoan;
use App\Support\ChargeCanteenCredit;
use App\Support\CharacterLoanLedgerEntries;
use App\Support\MemberCollectionAccounts;
use App\Support\MemberCreditLedger;
use App\Support\MemberCreditLimit;
use App\Support\PesoInput;
use App\Support\PrintCreditPaymentReceipt;
use App\Support\QuickLoanLedgerEntries;
use App\Support\RecordMemberLoanPayment;
use App\Support\RegularLoanSchedule;
use App\Support\SettleMemberCredit;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CollectionPayment extends Page
{
    protected static ?string $navigationLabel = 'Collection payment';

    protected static ?string $title = 'Collection payment';

    protected static ?string $slug = 'collection-payment';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.collection-cashier.collection-payment';

    public string $memberSearch = '';

    public ?int $selectedMemberId = null;

    public ?string $expandedKey = null;

    public ?string $collectKey = null;

    public ?int $collectLoanId = null;

    public ?int $regularViewLoanId = null;

    public string $paymentKind = CharacterLoanLedgerEntries::KIND_PRINCIPAL;

    public string $paymentAmount = '';

    public string $officialReceiptNo = '';

    public ?string $paymentError = null;

    public ?int $chargeMemberId = null;

    public string $chargeAmount = '';

    public ?string $chargeError = null;

    public bool $showReceiptModal = false;

    public ?int $receiptPaymentId = null;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Collection payment');
    }

    public function updatedMemberSearch(): void
    {
        $this->selectedMemberId = null;
        $this->expandedKey = null;
        $this->regularViewLoanId = null;
        $this->closeCollectForm();
    }

    /**
     * @return Collection<int, Member>
     */
    public function getSearchResults(): Collection
    {
        $term = trim($this->memberSearch);

        if ($term === '' || $this->selectedMemberId) {
            return collect();
        }

        return Member::query()
            ->orderedByName()
            ->where(function ($query) use ($term): void {
                $query->where('name', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%')
                    ->orWhere('contact_number', 'like', '%'.$term.'%');

                if (ctype_digit($term)) {
                    $query->orWhere('id', (int) $term);
                }
            })
            ->limit(20)
            ->get();
    }

    public function selectMember(int $memberId): void
    {
        $member = $this->findMember($memberId);

        if (! $member instanceof Member) {
            return;
        }

        $this->selectedMemberId = $member->id;
        $this->memberSearch = $member->name;
        $this->expandedKey = null;
        $this->regularViewLoanId = null;
        $this->closeCollectForm();
    }

    public function clearMember(): void
    {
        $this->selectedMemberId = null;
        $this->memberSearch = '';
        $this->expandedKey = null;
        $this->regularViewLoanId = null;
        $this->closeCollectForm();
    }

    public function getSelectedMember(): ?Member
    {
        return $this->selectedMemberId === null
            ? null
            : $this->findMember($this->selectedMemberId);
    }

    /**
     * @return array{ledgers: array<string, array<string, mixed>>}|null
     */
    public function getAccounts(): ?array
    {
        $member = $this->getSelectedMember();

        return $member instanceof Member
            ? MemberCollectionAccounts::for($member)
            : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getExpandedLedger(): ?array
    {
        return $this->findLedger((string) $this->expandedKey);
    }

    public function toggleAccount(string $key): void
    {
        $this->expandedKey = $this->expandedKey === $key ? null : $key;
        $this->closeCollectForm();

        if ($this->expandedKey === MemberCollectionAccounts::TYPE_REGULAR) {
            $this->regularViewLoanId = $this->regularLoansForSchedule()->first()?->id;
        }
    }

    /**
     * @return Collection<int, RegularLoan>
     */
    public function regularLoansForSchedule(): Collection
    {
        $member = $this->getSelectedMember();

        if (! $member instanceof Member) {
            return collect();
        }

        return RegularLoan::query()
            ->forUser($member)
            ->where('status', LoanStatus::Approved)
            ->orderedByLoanDate()
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return array{schedule: RegularLoanSchedule, blankAmounts: bool, loans: Collection<int, RegularLoan>}
     */
    public function getRegularScheduleView(): array
    {
        $loans = $this->regularLoansForSchedule();
        $loan = $loans->firstWhere('id', $this->regularViewLoanId) ?? $loans->first();

        return [
            'schedule' => $loan instanceof RegularLoan
                ? RegularLoanSchedule::fromLoan($loan)
                : RegularLoanSchedule::blank(),
            'blankAmounts' => ! $loan instanceof RegularLoan,
            'loans' => $loans,
        ];
    }

    /**
     * @return array{user: Member, entries: Collection}
     */
    public function getCharacterLedgerView(): array
    {
        $member = $this->getSelectedMember();

        $loans = $member instanceof Member
            ? CharacterLoan::query()
                ->forUser($member)
                ->where('status', LoanStatus::Approved)
                ->with('payments')
                ->orderedByLoanDate()
                ->orderByDesc('id')
                ->get()
            : collect();

        return [
            'user' => $member,
            'entries' => CharacterLoanLedgerEntries::forLoans($loans),
        ];
    }

    /**
     * @return array{user: Member, loan: ?QuickLoan, entries: Collection}
     */
    public function getQuickLedgerView(): array
    {
        $member = $this->getSelectedMember();

        $loans = $member instanceof Member
            ? QuickLoan::query()
                ->forUser($member)
                ->where('status', LoanStatus::Approved)
                ->with('payments')
                ->orderedByLoanDate()
                ->orderByDesc('id')
                ->get()
            : collect();

        return [
            'user' => $member,
            'loan' => $loans->first(),
            'entries' => QuickLoanLedgerEntries::forLoans($loans),
        ];
    }

    /**
     * @return array{user: Member, entries: Collection}
     */
    public function getCanteenLedgerView(): array
    {
        $member = $this->getSelectedMember();

        return [
            'user' => $member,
            'entries' => $member instanceof Member
                ? (new MemberCreditLedger($member))->entries(PosSaleChannel::Canteen)
                : collect(),
        ];
    }

    public function openCollectForm(string $key): void
    {
        $ledger = $this->findLedger($key);

        if ($ledger === null) {
            return;
        }

        $this->expandedKey = $key;
        $this->collectKey = $key;
        $this->paymentError = null;
        $this->officialReceiptNo = '';

        $accounts = collect($ledger['accounts']);

        if ($accounts->isEmpty()) {
            $this->collectKey = null;
            $this->collectLoanId = null;
            Notification::make()
                ->title(__('Nothing to collect'))
                ->body(__('This ledger has no remaining balance.'))
                ->warning()
                ->send();

            return;
        }

        $this->collectLoanId = $key === MemberCollectionAccounts::TYPE_CANTEEN
            ? null
            : (int) $accounts->first()['loan_id'];

        $this->fillPaymentFields($this->findOpenAccount($key));
    }

    public function closeCollectForm(): void
    {
        $this->collectKey = null;
        $this->collectLoanId = null;
        $this->paymentAmount = '';
        $this->officialReceiptNo = '';
        $this->paymentError = null;
        $this->paymentKind = CharacterLoanLedgerEntries::KIND_PRINCIPAL;
    }

    public function updatedCollectLoanId(): void
    {
        $this->paymentError = null;
        $this->fillPaymentFields($this->findOpenAccount((string) $this->collectKey));
    }

    public function updatedPaymentKind(): void
    {
        $account = $this->findOpenAccount((string) $this->collectKey);
        $loan = $account ? $this->resolveLoan($account) : null;

        if (! $loan instanceof MemberLoan) {
            return;
        }

        if ($this->paymentKind === CharacterLoanLedgerEntries::KIND_INTEREST) {
            $this->paymentAmount = PesoInput::format(CharacterLoanLedgerEntries::periodInterest($loan));

            return;
        }

        $remaining = RecordMemberLoanPayment::remainingPrincipal($loan);
        $this->paymentAmount = $remaining > 0 ? PesoInput::format($remaining) : '';
    }

    public function recordCollection(): void
    {
        $account = $this->findOpenAccount((string) $this->collectKey);

        if ($account === null) {
            $this->closeCollectForm();

            return;
        }

        if ($account['type'] === MemberCollectionAccounts::TYPE_CANTEEN) {
            $this->recordCanteenPayment($account);

            return;
        }

        $this->recordLoanPayment($account);
    }

    public function openChargeModal(): void
    {
        $member = $this->getSelectedMember();

        if (! $member instanceof Member) {
            return;
        }

        $remaining = MemberCreditLimit::remainingThisMonth($member->id, PosSaleChannel::Canteen);

        if ($remaining < 0.01) {
            Notification::make()
                ->title(__('Monthly limit reached'))
                ->body(__(':member has no remaining canteen credit this month.', [
                    'member' => $member->name,
                ]))
                ->warning()
                ->send();

            return;
        }

        $this->closeCollectForm();
        $this->chargeMemberId = $member->id;
        $this->chargeAmount = '';
        $this->chargeError = null;
    }

    public function closeChargeModal(): void
    {
        $this->chargeMemberId = null;
        $this->chargeAmount = '';
        $this->chargeError = null;
    }

    public function getChargeMember(): ?Member
    {
        return $this->chargeMemberId === null
            ? null
            : $this->findMember($this->chargeMemberId);
    }

    public function getChargeRemainingLimit(): float
    {
        $member = $this->getChargeMember();

        if (! $member instanceof Member) {
            return 0;
        }

        return MemberCreditLimit::remainingThisMonth($member->id, PosSaleChannel::Canteen);
    }

    public function recordCharge(): void
    {
        $member = $this->getChargeMember();

        if (! $member instanceof Member) {
            $this->closeChargeModal();

            return;
        }

        try {
            $result = ChargeCanteenCredit::apply($member, PesoInput::parse($this->chargeAmount), auth()->user());
        } catch (InvalidArgumentException $exception) {
            $this->chargeError = $exception->getMessage();

            return;
        }

        Notification::make()
            ->title(__('Charged to canteen credit'))
            ->body(__(':member — ₱:amount on account.', [
                'member' => $member->name,
                'amount' => number_format((float) $result['sale']->total, 2),
            ]))
            ->success()
            ->send();

        $this->closeChargeModal();
    }

    /**
     * @return array{payment: PosCreditPayment, cashier: ?\App\Models\User, balanceBefore: float, balanceAfter: float, autoPrint: bool}|null
     */
    public function getReceiptData(): ?array
    {
        if ($this->receiptPaymentId === null) {
            return null;
        }

        $payment = PosCreditPayment::query()
            ->with(['member', 'cashier'])
            ->find($this->receiptPaymentId);

        return $payment instanceof PosCreditPayment
            ? PrintCreditPaymentReceipt::viewData($payment)
            : null;
    }

    public function closeReceiptModal(): void
    {
        $this->showReceiptModal = false;
        $this->receiptPaymentId = null;
    }

    /**
     * @param  array<string, mixed>  $account
     */
    protected function recordCanteenPayment(array $account): void
    {
        $member = $this->getSelectedMember();

        if (! $member instanceof Member) {
            return;
        }

        $amount = PesoInput::parse($this->paymentAmount);
        $outstanding = (float) $account['balance'];

        if ($amount < 0.01) {
            $this->paymentError = __('Enter the amount the member is paying.');

            return;
        }

        if ($amount - $outstanding > SettleMemberCredit::EPSILON) {
            $this->paymentError = __('Payment cannot be more than the ₱:amount balance.', [
                'amount' => number_format($outstanding, 2),
            ]);

            return;
        }

        $result = SettleMemberCredit::apply($member, PosSaleChannel::Canteen, $amount, auth()->user());

        Notification::make()
            ->title(__('Payment recorded'))
            ->body(__(':member paid ₱:paid toward canteen credit.', [
                'member' => $member->name,
                'paid' => number_format($result['applied'], 2),
            ]))
            ->success()
            ->send();

        $this->closeCollectForm();

        if ($result['payment'] instanceof PosCreditPayment) {
            $this->receiptPaymentId = $result['payment']->id;
            $this->showReceiptModal = true;
        }
    }

    /**
     * @param  array<string, mixed>  $account
     */
    protected function recordLoanPayment(array $account): void
    {
        $loan = $this->resolveLoan($account);

        if (! $loan instanceof MemberLoan) {
            $this->paymentError = __('This loan could not be found.');

            return;
        }

        try {
            RecordMemberLoanPayment::apply(
                $loan,
                PesoInput::parse($this->paymentAmount),
                $this->paymentKind,
                $this->officialReceiptNo !== '' ? $this->officialReceiptNo : null,
                now(),
            );
        } catch (InvalidArgumentException $exception) {
            $this->paymentError = $exception->getMessage();

            return;
        }

        Notification::make()
            ->title(__('Payment recorded'))
            ->body(__('₱:amount applied to :account.', [
                'amount' => number_format(PesoInput::parse($this->paymentAmount), 2),
                'account' => $account['label'],
            ]))
            ->success()
            ->send();

        $this->closeCollectForm();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findLedger(string $type): ?array
    {
        $accounts = $this->getAccounts();

        if ($accounts === null || $type === '') {
            return null;
        }

        return $accounts['ledgers'][$type] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOpenAccount(string $key): ?array
    {
        $ledger = $this->findLedger($key);

        if ($ledger === null) {
            return null;
        }

        $accounts = collect($ledger['accounts']);

        if ($key === MemberCollectionAccounts::TYPE_CANTEEN) {
            return $accounts->first();
        }

        if ($this->collectLoanId === null) {
            return $accounts->first();
        }

        $loanId = (int) $this->collectLoanId;

        return $accounts->first(fn (array $account): bool => (int) ($account['loan_id'] ?? 0) === $loanId);
    }

    /**
     * @param  array<string, mixed>|null  $account
     */
    protected function fillPaymentFields(?array $account): void
    {
        if ($account === null) {
            return;
        }

        $this->paymentKind = $account['type'] === MemberCollectionAccounts::TYPE_CHARACTER
            ? CharacterLoanLedgerEntries::KIND_INTEREST
            : CharacterLoanLedgerEntries::KIND_PRINCIPAL;

        if ($account['type'] === MemberCollectionAccounts::TYPE_CHARACTER) {
            $loan = $this->resolveLoan($account);
            $this->paymentAmount = $loan
                ? PesoInput::format(CharacterLoanLedgerEntries::periodInterest($loan))
                : '';

            return;
        }

        $this->paymentAmount = PesoInput::format((float) $account['balance']);
    }

    /**
     * @param  array<string, mixed>  $account
     */
    protected function resolveLoan(array $account): ?MemberLoan
    {
        $id = $account['loan_id'] ?? null;

        if (! is_numeric($id)) {
            return null;
        }

        $model = match ($account['type']) {
            MemberCollectionAccounts::TYPE_QUICK => QuickLoan::class,
            MemberCollectionAccounts::TYPE_CHARACTER => CharacterLoan::class,
            default => RegularLoan::class,
        };

        return $model::query()->with('payments')->find((int) $id);
    }

    protected function findMember(int $memberId): ?Member
    {
        return Member::query()->find($memberId);
    }
}
