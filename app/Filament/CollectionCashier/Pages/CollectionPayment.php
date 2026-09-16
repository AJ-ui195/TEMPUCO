<?php

namespace App\Filament\CollectionCashier\Pages;

use App\Enums\LoanStatus;
use App\Enums\PosSaleChannel;
use App\Enums\ReceiptKind;
use App\Models\CancelledReceipt;
use App\Models\CharacterLoan;
use App\Models\Contracts\MemberLoan;
use App\Models\InvoiceFeePayment;
use App\Models\LoanPayment;
use App\Models\Member;
use App\Models\PosCreditPayment;
use App\Models\QuickLoan;
use App\Models\RegularLoan;
use App\Models\User;
use App\Support\CharacterLoanLedgerEntries;
use App\Support\ChargeCanteenCredit;
use App\Support\CollectionReceiptNumbers;
use App\Support\CollectionReceipts;
use App\Support\InvoiceFeeItems;
use App\Support\LoanTypes;
use App\Support\MemberCollectionAccounts;
use App\Support\MemberCreditLedger;
use App\Support\MemberCreditLimit;
use App\Support\PesoInput;
use App\Support\PrintCreditPaymentReceipt;
use App\Support\QuickLoanLedgerEntries;
use App\Support\RecordMemberLoanPayment;
use App\Support\RegularLoanPaymentAllocation;
use App\Support\RegularLoanSchedule;
use App\Support\SettleMemberCredit;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Navigation\NavigationItem;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Livewire\Attributes\Url;

use function Filament\Support\original_request;

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

    public bool $collectModalOpen = false;

    public ?int $collectLoanId = null;

    public ?int $regularViewLoanId = null;

    public string $paymentKind = CharacterLoanLedgerEntries::KIND_INTEREST;

    public string $paymentAmount = '0.00';

    public string $cashTendered = '';

    public string $invoiceInterest = '0.00';

    public string $invoiceSurcharge = '0.00';

    public string $invoiceMembershipFee = '0.00';

    public string $invoiceOthers = '0.00';

    public bool $editingInvoiceFees = false;

    public string $collectionMethod = 'cash';

    public string $officialReceiptNo = '';

    public string $invoiceNo = '';

    public string $receiptKind = '';

    public bool $kindChosen = false;

    #[Url(except: '')]
    public string $receipt = '';

    public string $paymentDate = '';

    public bool $showSavedModal = false;

    public string $savedTitle = '';

    public string $savedMessage = '';

    public string $savedTone = 'save';

    /**
     * @var array<string, mixed>
     */
    public array $printedPreview = [];

    public ?string $paymentError = null;

    public ?int $chargeMemberId = null;

    public string $chargeAmount = '';

    public ?string $chargeError = null;

    public bool $showReceiptModal = false;

    public ?int $receiptPaymentId = null;

    public ?int $editingLoanPaymentId = null;

    public ?int $editingPosPaymentId = null;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Collection payment');
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function mount(): void
    {
        $this->paymentDate = now()->toDateString();
        $this->refreshReceiptNumbers();

        if ($this->receipt === 'invoice') {
            $this->chooseReceiptKind(ReceiptKind::Invoice->value);
        } else {
            $this->chooseReceiptKind(ReceiptKind::OfficialReceipt->value);
        }
    }

    /**
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(): array
    {
        $url = static::getUrl();

        return [
            NavigationItem::make(static::getNavigationLabel())
                ->key(static::class)
                ->icon(static::getNavigationIcon())
                ->activeIcon(static::getActiveNavigationIcon())
                ->sort(static::getNavigationSort())
                ->url($url.'?receipt=or')
                ->isActiveWhen(fn (): bool => original_request()->routeIs(static::getRouteName()))
                ->childItems([
                    NavigationItem::make(__('Official Receipt'))
                        ->url($url.'?receipt=or')
                        ->isActiveWhen(fn (): bool => original_request()->routeIs(static::getRouteName())
                            && original_request()->query('receipt') === 'or')
                        ->sort(1),
                    NavigationItem::make(__('Invoice'))
                        ->url($url.'?receipt=invoice')
                        ->isActiveWhen(fn (): bool => original_request()->routeIs(static::getRouteName())
                            && original_request()->query('receipt') === 'invoice')
                        ->sort(2),
                ]),
        ];
    }

    public function refreshReceiptNumbers(): void
    {
        $this->officialReceiptNo = CollectionReceiptNumbers::nextOfficialReceiptNo();
        $this->invoiceNo = CollectionReceiptNumbers::nextInvoiceNo();
    }

    public function chooseReceiptKind(string $kind): void
    {
        if (! in_array($kind, [ReceiptKind::OfficialReceipt->value, ReceiptKind::Invoice->value], true)) {
            return;
        }

        $this->receiptKind = $kind;
        $this->kindChosen = true;
        $this->receipt = $kind === ReceiptKind::Invoice->value ? 'invoice' : 'or';
    }

    public function updatedOfficialReceiptNo(): void
    {
        $this->tryLoadFromReceiptNumber(false);
    }

    public function updatedInvoiceNo(): void
    {
        $this->tryLoadFromReceiptNumber(false);
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

        if ($term === '' || $this->selectedMemberId || $this->isCancelledAccountLabel($term)) {
            return collect();
        }

        return Member::query()
            ->orderedByName()
            ->where('name', 'like', '%'.$term.'%')
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
        $this->resetInvoiceFees();
        $this->collectionMethod = 'cash';
    }

    public function clearMember(): void
    {
        $this->selectedMemberId = null;
        $this->memberSearch = '';
        $this->expandedKey = null;
        $this->regularViewLoanId = null;
        $this->closeCollectForm();
        $this->resetInvoiceFees();
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

        if (MemberCollectionAccounts::isSalaryLedger((string) $this->expandedKey)) {
            $this->regularViewLoanId = $this->regularLoansForSchedule()->first()?->id;
        }

        $ledger = $this->getExpandedLedger();

        if ($ledger !== null) {
            $this->paymentKind = CharacterLoanLedgerEntries::KIND_PRINCIPAL;
            $this->paymentAmount = $this->pesoOrZero($ledger['balance']);
        }
    }

    public function getOverallAmount(): float
    {
        return PesoInput::parse($this->cashTendered);
    }

    public function isInvoiceMode(): bool
    {
        return $this->receiptKind === ReceiptKind::Invoice->value;
    }

    /**
     * @return array<string, string>
     */
    public function getInvoiceFeeLabels(): array
    {
        return InvoiceFeeItems::labels();
    }

    public function normalizeInvoiceFees(): void
    {
        $this->invoiceInterest = $this->pesoOrZero($this->invoiceInterest);
        $this->invoiceSurcharge = $this->pesoOrZero($this->invoiceSurcharge);
        $this->invoiceMembershipFee = $this->pesoOrZero($this->invoiceMembershipFee);
        $this->invoiceOthers = $this->pesoOrZero($this->invoiceOthers);
    }

    public function updatedInvoiceInterest(mixed $value): void
    {
        $this->invoiceInterest = $this->digitsOnly($value);
    }

    public function updatedInvoiceSurcharge(mixed $value): void
    {
        $this->invoiceSurcharge = $this->digitsOnly($value);
    }

    public function updatedInvoiceMembershipFee(mixed $value): void
    {
        $this->invoiceMembershipFee = $this->digitsOnly($value);
    }

    public function updatedInvoiceOthers(mixed $value): void
    {
        $this->invoiceOthers = $this->digitsOnly($value);
    }

    public function updatedPaymentAmount(mixed $value): void
    {
        $this->paymentAmount = $this->digitsOnly($value);
    }

    public function normalizePaymentAmount(): void
    {
        $this->paymentAmount = $this->pesoOrZero($this->paymentAmount);
    }

    public function getTotalAmount(): float
    {
        if ($this->isInvoiceMode()) {
            $total = 0.0;

            foreach ($this->invoiceFeeAmounts() as $amount) {
                $total = round($total + PesoInput::parse($amount), 2);
            }

            return $total;
        }

        return PesoInput::parse($this->paymentAmount);
    }

    public function getChangeAmount(): float
    {
        return round(max(0, $this->getOverallAmount() - $this->getTotalAmount()), 2);
    }

    public function savePayment(): void
    {
        $this->paymentError = null;
        $this->editingLoanPaymentId = null;
        $this->editingPosPaymentId = null;

        if (! $this->assertSaveGuards()) {
            return;
        }

        if ($this->getSelectedMember() === null) {
            $this->paymentError = __('Select a member to collect.');

            return;
        }

        if ($this->isInvoiceMode()) {
            $this->recordInvoiceFees();

            return;
        }

        if ($this->expandedKey === null || $this->expandedKey === '') {
            $this->paymentError = __('Select an account.');

            return;
        }

        $ledger = $this->findLedger($this->expandedKey);

        if ($ledger === null || collect($ledger['accounts'])->isEmpty()) {
            Notification::make()
                ->title(__('Nothing to collect'))
                ->body(__('This ledger has no remaining balance.'))
                ->warning()
                ->send();

            return;
        }

        $this->collectKey = $this->expandedKey;
        $this->collectLoanId = $this->expandedKey === MemberCollectionAccounts::TYPE_CANTEEN
            ? null
            : (int) collect($ledger['accounts'])->first()['loan_id'];

        $this->recordCollection();
    }

    public function cancelOfficialReceipt(): void
    {
        $this->paymentError = null;
        $kind = $this->receiptKind !== '' ? $this->receiptKind : ReceiptKind::OfficialReceipt->value;
        $number = CollectionReceipts::currentNumber($kind, $this->officialReceiptNo, $this->invoiceNo);

        if ($number === '') {
            $this->paymentError = $kind === ReceiptKind::Invoice->value
                ? __('Enter the invoice number.')
                : __('Enter the O.R. number.');

            return;
        }

        if (CollectionReceipts::isTaken($number, $kind)) {
            $this->paymentError = __('This number is already used or cancelled.');

            return;
        }

        CancelledReceipt::query()->create([
            'number' => $number,
            'kind' => $kind,
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
        $kind = $this->receiptKind !== '' ? $this->receiptKind : ReceiptKind::OfficialReceipt->value;
        $number = CollectionReceipts::currentNumber($kind, $this->officialReceiptNo, $this->invoiceNo);

        if ($number === '') {
            $this->paymentError = $kind === ReceiptKind::Invoice->value
                ? __('Enter the invoice number to delete.')
                : __('Enter the O.R. number to delete.');

            return;
        }

        if (CollectionReceipts::isCancelled($number, $kind)) {
            $this->paymentError = __('This number was cancelled and cannot be deleted as a payment.');

            return;
        }

        $hit = CollectionReceipts::findPosted($number, $kind);
        $loanPayment = $hit['loan'];
        $canteenPayment = $hit['canteen'];
        $invoiceFees = $kind === ReceiptKind::Invoice->value
            ? CollectionReceipts::findInvoiceFees($number)
            : collect();

        if (
            ! $loanPayment instanceof LoanPayment
            && ! $canteenPayment instanceof PosCreditPayment
            && $invoiceFees->isEmpty()
        ) {
            $this->paymentError = __('No payment found for this number.');

            return;
        }

        if ($invoiceFees->isNotEmpty()) {
            $invoiceFees->each->delete();
        }

        if ($loanPayment instanceof LoanPayment) {
            $loanPayment->delete();
        }

        if ($canteenPayment instanceof PosCreditPayment) {
            $canteenPayment->delete();
        }

        $this->editingLoanPaymentId = null;
        $this->editingPosPaymentId = null;
        $this->paymentAmount = '0.00';
        $this->cashTendered = '';
        $this->expandedKey = null;
        $this->regularViewLoanId = null;
        $this->closeCollectForm();
        $this->resetInvoiceFees();
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
        $kind = $this->receiptKind !== '' ? $this->receiptKind : ReceiptKind::OfficialReceipt->value;
        $number = CollectionReceipts::currentNumber($kind, $this->officialReceiptNo, $this->invoiceNo);

        if ($number === '') {
            $this->paymentError = $kind === ReceiptKind::Invoice->value
                ? __('Enter the invoice number.')
                : __('Enter the O.R. number.');

            return;
        }

        if (CollectionReceipts::isCancelled($number, $kind)) {
            $this->paymentError = __('This number was cancelled and cannot be updated.');

            return;
        }

        $hit = CollectionReceipts::findPosted($number, $kind);
        $loanPayment = $hit['loan'];
        $canteenPayment = $hit['canteen'];
        $invoiceFees = $kind === ReceiptKind::Invoice->value
            ? CollectionReceipts::findInvoiceFees($number)
            : collect();

        if ($invoiceFees->isNotEmpty()) {
            if (! $this->editingInvoiceFees) {
                $this->loadInvoiceFeePayment($invoiceFees);
                $this->openSavedModal(
                    __('Payment loaded'),
                    __('Account information was loaded for :number. Change the amount, then click Update payment again.', [
                        'number' => $number,
                    ]),
                    'update',
                );

                return;
            }

            if (! $this->assertCashTendered()) {
                return;
            }

            $this->recordInvoiceFees();

            return;
        }

        if (! $loanPayment instanceof LoanPayment && ! $canteenPayment instanceof PosCreditPayment) {
            $this->paymentError = __('No payment found for this number.');

            return;
        }

        $alreadyLoaded = ($loanPayment instanceof LoanPayment && $this->editingLoanPaymentId === $loanPayment->id)
            || ($canteenPayment instanceof PosCreditPayment && $this->editingPosPaymentId === $canteenPayment->id);

        if (! $alreadyLoaded) {
            $this->loadPostedPayment($loanPayment, $canteenPayment);

            if ($this->selectedMemberId) {
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

        if (! $this->assertCashTendered()) {
            return;
        }

        try {
            if ($loanPayment instanceof LoanPayment) {
                RecordMemberLoanPayment::revise($loanPayment, PesoInput::parse($this->paymentAmount));
            }

            if ($canteenPayment instanceof PosCreditPayment) {
                $this->reviseCanteenPayment($canteenPayment);
            }
        } catch (InvalidArgumentException $exception) {
            $this->paymentError = $exception->getMessage();

            return;
        }

        $this->openSavedModal(
            __('Payment updated'),
            __('Receipt :number was updated in the database.', ['number' => $number]),
            'update',
        );
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

        $query = RegularLoan::query()
            ->forUser($member)
            ->where('status', LoanStatus::Approved)
            ->with('payments')
            ->orderedByLoanDate()
            ->orderByDesc('id');

        if ($this->expandedKey === MemberCollectionAccounts::TYPE_SALARY_2) {
            $query->whereRaw('UPPER(TRIM(loan_type)) = ?', [LoanTypes::SALARY_2]);
        } else {
            $query->where(function ($inner): void {
                $inner->whereNull('loan_type')
                    ->orWhereRaw('UPPER(TRIM(loan_type)) <> ?', [LoanTypes::SALARY_2]);
            });
        }

        return $query->get();
    }

    /**
     * @return array{schedule: RegularLoanSchedule, blankAmounts: bool, loans: Collection<int, RegularLoan>, loan: ?RegularLoan, paidCash: float}
     */
    public function getRegularScheduleView(): array
    {
        $loans = $this->regularLoansForSchedule();
        $loan = $loans->firstWhere('id', $this->regularViewLoanId) ?? $loans->first();

        return [
            'schedule' => $loan instanceof RegularLoan
                ? RegularLoanSchedule::fromLoan($loan)
                : RegularLoanSchedule::blank($this->expandedKey === MemberCollectionAccounts::TYPE_SALARY_2),
            'blankAmounts' => ! $loan instanceof RegularLoan,
            'loans' => $loans,
            'loan' => $loan instanceof RegularLoan ? $loan : null,
            'paidCash' => $loan instanceof RegularLoan
                ? RegularLoanPaymentAllocation::cashPaid($loan)
                : 0.0,
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
                ? (new MemberCreditLedger($member))->entries()
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
        $this->collectModalOpen = true;
        $this->paymentError = null;

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
        $this->collectModalOpen = false;
        $this->editingLoanPaymentId = null;
        $this->editingPosPaymentId = null;
        $this->paymentAmount = '0.00';
        $this->cashTendered = '';
        $this->paymentError = null;
        $this->paymentKind = CharacterLoanLedgerEntries::KIND_INTEREST;
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
            $this->paymentAmount = $this->pesoOrZero(CharacterLoanLedgerEntries::periodInterest($loan));

            return;
        }

        $remaining = RecordMemberLoanPayment::remainingPrincipal($loan);
        $this->paymentAmount = $this->pesoOrZero($remaining);
    }

    public function recordCollection(): void
    {
        if (! $this->kindChosen) {
            return;
        }

        if (! $this->assertSaveGuards()) {
            return;
        }

        if (! in_array($this->collectionMethod, ['cash', 'check'], true)) {
            $this->paymentError = __('Select Cash or Checks.');

            return;
        }

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

        $remaining = MemberCreditLimit::remaining($member->id, PosSaleChannel::Canteen);

        if ($remaining < 0.01) {
            Notification::make()
                ->title(__('Credit limit reached'))
                ->body(__(':member has no remaining canteen credit. Settle the account to free the ₱:limit limit.', [
                    'member' => $member->name,
                    'limit' => number_format(MemberCreditLimit::LIMIT, 2),
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

        return MemberCreditLimit::remaining($member->id, PosSaleChannel::Canteen);
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
     * @return array{payment: PosCreditPayment, cashier: ?User, balanceBefore: float, balanceAfter: float, autoPrint: bool}|null
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
     * @return array<string, string>
     */
    protected function invoiceFeeAmounts(): array
    {
        return [
            InvoiceFeeItems::INTEREST => $this->invoiceInterest,
            InvoiceFeeItems::SURCHARGE => $this->invoiceSurcharge,
            InvoiceFeeItems::MEMBERSHIP_FEE => $this->invoiceMembershipFee,
            InvoiceFeeItems::OTHERS => $this->invoiceOthers,
        ];
    }

    protected function resetInvoiceFees(): void
    {
        $this->invoiceInterest = '0.00';
        $this->invoiceSurcharge = '0.00';
        $this->invoiceMembershipFee = '0.00';
        $this->invoiceOthers = '0.00';
        $this->editingInvoiceFees = false;
    }

    protected function pesoOrZero(mixed $value): string
    {
        return number_format(PesoInput::parse($value), 2, '.', ',');
    }

    protected function digitsOnly(mixed $value): string
    {
        return preg_replace('/[^0-9.,]/', '', (string) $value) ?? '';
    }

    /**
     * @param  Collection<int, InvoiceFeePayment>  $fees
     */
    protected function loadInvoiceFeePayment(Collection $fees): void
    {
        $fee = $fees->first();

        if (! $fee instanceof InvoiceFeePayment) {
            return;
        }

        $fee->load('member');
        $member = $fee->member;

        if (! $member instanceof Member) {
            $this->paymentError = __('This member could not be found.');

            return;
        }

        $this->resetInvoiceFees();
        $this->selectedMemberId = $member->id;
        $this->memberSearch = $member->name;
        $this->editingInvoiceFees = true;
        $this->collectionMethod = in_array((string) $fee->collection_method, ['cash', 'check'], true)
            ? (string) $fee->collection_method
            : 'cash';
        $this->cashTendered = $this->pesoOrZero($fee->totalAmount());
        $this->invoiceInterest = $this->pesoOrZero($fee->interest);
        $this->invoiceSurcharge = $this->pesoOrZero($fee->surcharge);
        $this->invoiceMembershipFee = $this->pesoOrZero($fee->membership_fee);
        $this->invoiceOthers = $this->pesoOrZero($fee->others);
    }

    protected function recordInvoiceFees(): void
    {
        $member = $this->getSelectedMember();

        if (! $member instanceof Member) {
            $this->paymentError = __('Select a member to collect.');

            return;
        }

        if (! in_array($this->collectionMethod, ['cash', 'check'], true)) {
            $this->paymentError = __('Select Cash or Checks.');

            return;
        }

        $total = $this->getTotalAmount();

        if ($total < 0.01) {
            $this->paymentError = __('Enter at least one invoice amount.');

            return;
        }

        $wasUpdate = $this->editingInvoiceFees;
        $memberName = $member->name;
        $invoiceNo = trim($this->invoiceNo);
        $receivedAt = filled($this->paymentDate)
            ? \Illuminate\Support\Carbon::parse($this->paymentDate)
            : now();

        DB::transaction(function () use ($member, $invoiceNo, $receivedAt, $total): void {
            InvoiceFeePayment::query()
                ->whereIn('invoice_no', CollectionReceipts::candidates($invoiceNo))
                ->delete();

            InvoiceFeePayment::query()->create([
                'member_id' => $member->id,
                'invoice_no' => $invoiceNo,
                'interest' => PesoInput::parse($this->invoiceInterest),
                'surcharge' => PesoInput::parse($this->invoiceSurcharge),
                'membership_fee' => PesoInput::parse($this->invoiceMembershipFee),
                'others' => PesoInput::parse($this->invoiceOthers),
                'amount' => $total,
                'collection_method' => $this->collectionMethod,
                'received_by' => auth()->id(),
                'received_at' => $receivedAt,
            ]);
        });

        $this->resetInvoiceFees();
        $this->refreshReceiptNumbers();
        $this->openSavedModal(
            $wasUpdate ? __('Payment updated') : __('Payment saved'),
            __('Invoice :number was recorded for :member — ₱:amount.', [
                'number' => $invoiceNo,
                'member' => $memberName,
                'amount' => number_format($total, 2),
            ]),
            $wasUpdate ? 'update' : 'save',
        );
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

        if ($this->receiptKind === ReceiptKind::OfficialReceipt->value && trim($this->officialReceiptNo) === '') {
            $this->paymentError = __('Enter the O.R. number.');

            return;
        }

        if ($this->receiptKind === ReceiptKind::Invoice->value && trim($this->invoiceNo) === '') {
            $this->paymentError = __('Enter the invoice number.');

            return;
        }

        $printKind = $this->receiptKind;
        $result = SettleMemberCredit::applyAcrossChannels(
            $member,
            $amount,
            auth()->user(),
            $printKind === ReceiptKind::OfficialReceipt->value ? ($this->officialReceiptNo !== '' ? $this->officialReceiptNo : null) : null,
            $printKind === ReceiptKind::Invoice->value ? ($this->invoiceNo !== '' ? $this->invoiceNo : null) : null,
            $printKind,
        );

        $this->printedPreview = $this->receiptPreview();
        $this->closeCollectForm();
        $this->refreshReceiptNumbers();
        $this->openSavedModal(
            __('Payment saved'),
            __(':member paid ₱:paid toward canteen / grocery credit.', [
                'member' => $member->name,
                'paid' => number_format($result['applied'], 2),
            ]),
            'save',
        );

        if ($result['payment'] instanceof PosCreditPayment) {
            $this->receiptPaymentId = $result['payment']->id;
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

        if (trim($this->officialReceiptNo) === '') {
            $this->paymentError = __('Enter the O.R. number.');

            return;
        }

        try {
            RecordMemberLoanPayment::apply(
                $loan,
                PesoInput::parse($this->paymentAmount),
                $this->paymentKind,
                $this->officialReceiptNo,
                now(),
                ReceiptKind::OfficialReceipt->value,
                ($staffId = auth()->guard('web')->id()) !== null ? (int) $staffId : null,
            );
        } catch (InvalidArgumentException $exception) {
            $this->paymentError = $exception->getMessage();

            return;
        }

        $this->printedPreview = $this->receiptPreview();
        $amountPaid = PesoInput::parse($this->paymentAmount);
        $accountLabel = $account['label'];

        $this->closeCollectForm();
        $this->refreshReceiptNumbers();
        $this->openSavedModal(
            __('Payment saved'),
            __('₱:amount applied to :account.', [
                'amount' => number_format($amountPaid, 2),
                'account' => $accountLabel,
            ]),
            'save',
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

    /**
     * @return array<string, mixed>
     */
    public function receiptPreview(): array
    {
        $member = $this->getSelectedMember();
        $account = $this->collectKey ? $this->findOpenAccount($this->collectKey) : null;
        $ledger = $this->collectKey ? $this->findLedger($this->collectKey) : ($this->expandedKey ? $this->findLedger($this->expandedKey) : null);
        $label = $account['label'] ?? $ledger['label'] ?? '';
        $date = filled($this->paymentDate)
            ? \Illuminate\Support\Carbon::parse($this->paymentDate)->format('m-d-y')
            : now()->format('m-d-y');

        return [
            'receivedFrom' => $member?->name ?? '',
            'soldTo' => $member?->name ?? '',
            'tin' => (string) ($member?->tin_number ?? ''),
            'paymentFor' => $label,
            'item' => $label,
            'amount' => PesoInput::parse($this->paymentAmount),
            'date' => $date,
            'orNo' => $this->officialReceiptNo,
            'invoiceNo' => $this->invoiceNo,
            'cash' => match ($this->collectionMethod) {
                'cash' => true,
                'check' => false,
                default => null,
            },
        ];
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
                ? $this->pesoOrZero(CharacterLoanLedgerEntries::periodInterest($loan))
                : '0.00';

            return;
        }

        $this->paymentAmount = $this->pesoOrZero($account['balance']);
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

    protected function tryLoadFromReceiptNumber(bool $notifyMissing): void
    {
        $kind = $this->receiptKind !== '' ? $this->receiptKind : ReceiptKind::OfficialReceipt->value;
        $number = CollectionReceipts::currentNumber($kind, $this->officialReceiptNo, $this->invoiceNo);

        if ($number === '') {
            return;
        }

        if (CollectionReceipts::isCancelled($number, $kind)) {
            $this->showCancelledAccount();

            if ($notifyMissing) {
                $this->paymentError = __('This number was cancelled and cannot be updated.');
            }

            return;
        }

        if ($this->isInvoiceMode()) {
            $invoiceFees = CollectionReceipts::findInvoiceFees($number);

            if ($invoiceFees->isNotEmpty()) {
                $this->loadInvoiceFeePayment($invoiceFees);

                return;
            }
        }

        $hit = CollectionReceipts::findPosted($number, $kind);
        $loanPayment = $hit['loan'];
        $canteenPayment = $hit['canteen'];

        if (! $loanPayment instanceof LoanPayment && ! $canteenPayment instanceof PosCreditPayment) {
            $this->editingLoanPaymentId = null;
            $this->editingPosPaymentId = null;

            if ($this->isCancelledAccountLabel($this->memberSearch) && $this->selectedMemberId === null) {
                $this->memberSearch = '';
            }

            if ($notifyMissing) {
                $this->paymentError = __('No payment found for this number.');
            }

            return;
        }

        $this->loadPostedPayment($loanPayment, $canteenPayment);
    }

    protected function showCancelledAccount(): void
    {
        $this->selectedMemberId = null;
        $this->memberSearch = __('Cancelled');
        $this->expandedKey = null;
        $this->regularViewLoanId = null;
        $this->editingLoanPaymentId = null;
        $this->editingPosPaymentId = null;
        $this->collectKey = null;
        $this->collectLoanId = null;
        $this->collectModalOpen = false;
        $this->paymentAmount = '0.00';
        $this->cashTendered = '';
        $this->resetInvoiceFees();
        $this->paymentError = null;
    }

    protected function isCancelledAccountLabel(string $value): bool
    {
        return strcasecmp(trim($value), (string) __('Cancelled')) === 0;
    }

    protected function assertSaveGuards(): bool
    {
        return $this->assertCashTendered() && $this->assertReceiptAvailable();
    }

    protected function assertCashTendered(): bool
    {
        $total = $this->getTotalAmount();
        $tendered = $this->getOverallAmount();

        if ($tendered + 0.001 < $total) {
            $this->paymentError = __('Cash tendered cannot be less than the total payment.');

            return false;
        }

        return true;
    }

    protected function assertReceiptAvailable(): bool
    {
        $kind = $this->receiptKind !== '' ? $this->receiptKind : ReceiptKind::OfficialReceipt->value;
        $number = CollectionReceipts::currentNumber($kind, $this->officialReceiptNo, $this->invoiceNo);

        if ($number === '') {
            $this->paymentError = $kind === ReceiptKind::Invoice->value
                ? __('Enter the invoice number.')
                : __('Enter the O.R. number.');

            return false;
        }

        if (CollectionReceipts::isTaken(
            $number,
            $kind,
            $this->editingLoanPaymentId,
            $this->editingPosPaymentId,
            $this->editingInvoiceFees ? $number : null,
        )) {
            $this->paymentError = __('This receipt number is already used or cancelled.');

            return false;
        }

        return true;
    }

    protected function loadPostedPayment(?LoanPayment $loanPayment, ?PosCreditPayment $canteenPayment): void
    {
        $this->collectModalOpen = false;
        $this->paymentError = null;
        $this->editingLoanPaymentId = $loanPayment?->id;
        $this->editingPosPaymentId = $canteenPayment?->id;

        if ($loanPayment instanceof LoanPayment) {
            $loanPayment->load(['characterLoan.member', 'quickLoan.member', 'regularLoan.member']);
            $loan = $loanPayment->loan();
            $member = $loan?->member ?? $loan?->user;

            if (! $loan instanceof MemberLoan || ! $member instanceof Member) {
                $this->paymentError = __('This loan could not be found.');

                return;
            }

            $this->selectedMemberId = $member->id;
            $this->memberSearch = $member->name;
            $this->expandedKey = MemberCollectionAccounts::ledgerType($loan);
            $this->collectKey = $this->expandedKey;
            $this->collectLoanId = $loan->getKey();
            $this->regularViewLoanId = $loan instanceof RegularLoan ? $loan->id : null;
            $this->paymentKind = (string) ($loanPayment->kind ?: CharacterLoanLedgerEntries::KIND_PRINCIPAL);
            $this->paymentAmount = $this->pesoOrZero($loanPayment->amount);
            $this->cashTendered = $this->paymentAmount;
            $this->collectionMethod = 'cash';

            return;
        }

        if (! $canteenPayment instanceof PosCreditPayment) {
            return;
        }

        $canteenPayment->load('member');
        $member = $canteenPayment->member;

        if (! $member instanceof Member) {
            $this->paymentError = __('This member could not be found.');

            return;
        }

        $this->selectedMemberId = $member->id;
        $this->memberSearch = $member->name;
        $this->expandedKey = MemberCollectionAccounts::TYPE_CANTEEN;
        $this->collectKey = MemberCollectionAccounts::TYPE_CANTEEN;
        $this->collectLoanId = null;
        $this->regularViewLoanId = null;
        $this->paymentKind = CharacterLoanLedgerEntries::KIND_PRINCIPAL;
        $this->paymentAmount = $this->pesoOrZero($canteenPayment->amount);
        $this->cashTendered = $this->paymentAmount;
        $this->collectionMethod = 'cash';
    }

    protected function reviseCanteenPayment(PosCreditPayment $payment): void
    {
        $member = $this->getSelectedMember();

        if (! $member instanceof Member) {
            throw new InvalidArgumentException(__('Select a member to collect.'));
        }

        $amount = PesoInput::parse($this->paymentAmount);
        $ledger = $this->findLedger(MemberCollectionAccounts::TYPE_CANTEEN);
        $outstanding = (float) ($ledger['balance'] ?? 0) + (float) $payment->amount;

        if ($amount < 0.01) {
            throw new InvalidArgumentException(__('Enter the amount the member is paying.'));
        }

        if ($amount - $outstanding > SettleMemberCredit::EPSILON) {
            throw new InvalidArgumentException(__('Payment cannot be more than the ₱:amount balance.', [
                'amount' => number_format($outstanding, 2),
            ]));
        }

        $payment->amount = $amount;
        $payment->save();
    }

    protected function findMember(int $memberId): ?Member
    {
        return Member::query()->find($memberId);
    }
}
