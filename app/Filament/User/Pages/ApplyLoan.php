<?php

namespace App\Filament\User\Pages;

use App\Enums\LoanCategory;
use App\Enums\LoanPurpose;
use App\Enums\ModeOfPayment;
use App\Models\Member;
use App\Support\ApdsRules;
use App\Support\CharacterLoanRules;
use App\Support\CollateralizedLoanRules;
use App\Support\LoanApplicationException;
use App\Support\LoanApplicationService;
use App\Support\LoanTypes;
use App\Support\PesoInput;
use App\Support\QuickLoanLedgerEntries;
use App\Support\RegularLoanSchedule;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class ApplyLoan extends Page
{
    private const APPLICATION_TYPE_REGULAR = 'regular';

    private const APPLICATION_TYPE_QUICK = 'quick';

    private const APPLICATION_TYPE_CHARACTER = 'character';

    protected static ?string $navigationLabel = 'Loan application';

    protected static ?string $title = 'Loan application';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentPlus;

    protected static ?int $navigationSort = -1;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $member = auth()->user();

        return $member instanceof Member && $member->isActive();
    }

    public function mount(): void
    {
        /** @var Member $member */
        $member = auth()->user();

        $dateOfBirth = $member->date_of_birth instanceof Carbon
            ? $member->date_of_birth->toDateString()
            : null;

        $this->form->fill([
            'loan_application_type' => self::APPLICATION_TYPE_REGULAR,
            'applicant_name' => $member->name,
            'applicant_address' => $member->address,
            'applicant_signed_at' => now()->toDateString(),
            'date_of_birth' => $dateOfBirth,
            'quick_date_of_birth' => $dateOfBirth,
            'quick_age' => $member->age()
                ?? ($dateOfBirth ? Carbon::parse($dateOfBirth)->age : null),
            'quick_sex' => $member->sex,
            'quick_civil_status' => $member->civil_status,
            'quick_contact_number' => $member->contact_number,
            'quick_email' => $member->email,
            'quick_occupation' => $member->occupation,
            'quick_employer_department' => $member->employer_department,
            'loan_period_months' => 12,
            'loan_type' => LoanTypes::REGULAR,
            'first_payment_due_date' => now()->addMonthNoOverflow()->toDateString(),
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Loan application');
    }

    public function collateralizedRequirementsAction(): Action
    {
        /** @var Member $member */
        $member = auth()->user();

        return Action::make('collateralizedRequirements')
            ->label(__('Requirements'))
            ->modalHeading(__('Collateralized loan'))
            ->modalDescription(__('Please review the documentary requirements and loan terms.'))
            ->modalContent(fn (): HtmlString => new HtmlString(
                view('filament.user.collateralized-loan-requirements', [
                    'member' => $member,
                ])->render()
            ))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('Close'))
            ->hidden();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('form_header')
                    ->hiddenLabel()
                    ->state(fn (): HtmlString => new HtmlString(
                        view('filament.user.loan-application-header')->render()
                    ))
                    ->columnSpanFull(),
                $this->loanApplicationTypeSection(),
                $this->loanCategorySection(),
                $this->regularLoanSection(),
                $this->quickLoanSection(),
                $this->characterLoanSection(),
                Section::make(__('Purpose and payment'))
                    ->description(__('Select the purpose of the loan and how you will pay.'))
                    ->schema($this->purposeAndPaymentFields())
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    protected function loanApplicationTypeSection(): Section
    {
        return Section::make(__('Loan application type'))
            ->schema([
                Radio::make('loan_application_type')
                    ->label(__('Choose loan form'))
                    ->options([
                        self::APPLICATION_TYPE_REGULAR => __('Regular loan'),
                        self::APPLICATION_TYPE_QUICK => __('Quick loan'),
                        self::APPLICATION_TYPE_CHARACTER => __('Character loan'),
                    ])
                    ->inline()
                    ->live()
                    ->afterStateUpdated(function (?string $state, callable $set, callable $get): void {
                        if ($state === self::APPLICATION_TYPE_QUICK) {
                            $set('loan_type', LoanTypes::QUICK);
                            $set('loan_period_months', 1);
                            $this->syncQuickPayable($set, $get);
                            $this->syncQuickDueDate($set, $get);

                            return;
                        }

                        if ($state === self::APPLICATION_TYPE_CHARACTER) {
                            $set('character_loan_type', LoanTypes::CHARACTER);
                            $this->syncCharacterDefaults($set, $get);

                            return;
                        }

                        $set('loan_type', LoanTypes::REGULAR);
                        $set('loan_period_months', 12);
                    })
                    ->required()
                    ->columnSpanFull(),
            ])
            ->columnSpanFull();
    }

    protected function loanCategorySection(): Section
    {
        return Section::make(__('Loan category'))
            ->schema([
                Radio::make('loan_category')
                    ->label(__('Select loan category'))
                    ->options(collect(LoanCategory::cases())->mapWithKeys(
                        fn (LoanCategory $category): array => [$category->value => $category->getLabel()]
                    )->all())
                    ->inline()
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_REGULAR)
                    ->visible(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_REGULAR)
                    ->columnSpanFull(),
            ])
            ->visible(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_REGULAR)
            ->columnSpanFull();
    }

    protected function regularLoanSection(): Section
    {
        return Section::make(__('Applicant\'s request (Regular loan)'))
            ->description(__('Salary loan 1 is the first APDS account (up to ₱300,000). Salary loan 2 is the second APDS account: 6% other charges and ₱5,000 capital build-up from net proceeds (Section 5). I hereby certify that all statements made hereon are true and complete.'))
            ->schema([
                TextInput::make('applicant_name')
                    ->label(__('Name'))
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_REGULAR)
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('applicant_address')
                    ->label(__('Address'))
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_REGULAR)
                    ->rows(2)
                    ->columnSpanFull(),
                DatePicker::make('date_of_birth')
                    ->label(__('Date of birth'))
                    ->native(false)
                    ->maxDate(now())
                    ->helperText(__('Optional. Age caps if DOB is set: 4yr≤56, 5yr≤55, 6yr≤54, 7yr≤53. Salary loan 1–2 terms are 1–7 years.'))
                    ->columnSpanFull(),
                Select::make('loan_type')
                    ->label(__('Type of loan'))
                    ->options(LoanTypes::regularOptions())
                    ->default(LoanTypes::REGULAR)
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_REGULAR)
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(fn (callable $set, callable $get) => $this->syncRegularInstallment($set, $get))
                    ->helperText(function (callable $get): string {
                        if (ApdsRules::isSecondAccountType((string) ($get('loan_type') ?? ''))) {
                            return __('Second APDS account. Other charges 6.00% (service 2%, insurance/processing/notarial/verification 1% each). ₱:cbu retained from net proceeds for share capital (Section 5).', [
                                'cbu' => number_format(ApdsRules::CAPITAL_BUILD_UP_RETENTION, 2),
                            ]);
                        }

                        return __('First APDS account. Other charges 5.25% for 1–5 years (service 1.25%) or 6.00% for 6–7 years (service 2%). ₱:cbu capital build-up from net proceeds.', [
                            'cbu' => number_format(ApdsRules::FIRST_APDS_CAPITAL_BUILD_UP, 2),
                        ]);
                    })
                    ->visible(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_REGULAR)
                    ->columnSpanFull(),
                PesoInput::decorate(
                    TextInput::make('loan_amount')
                        ->label(__('Loan amount (PHP)'))
                        ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_REGULAR)
                        ->minValue(1)
                        ->maxValue(function (callable $get): float {
                            if ($get('loan_application_type') === self::APPLICATION_TYPE_QUICK) {
                                return QuickLoanLedgerEntries::MAX_AMOUNT;
                            }

                            if (! ApdsRules::isSecondAccountType((string) ($get('loan_type') ?? ''))) {
                                return ApdsRules::FIRST_APDS_MAX_AMOUNT;
                            }

                            return 999999999;
                        })
                        ->helperText(function (callable $get): string {
                            if (ApdsRules::isSecondAccountType((string) ($get('loan_type') ?? ''))) {
                                return __('Second APDS (Salary loan 2): ₱:cbu automatic retention from net proceeds credited to share capital (Section 5).', [
                                    'cbu' => number_format(ApdsRules::CAPITAL_BUILD_UP_RETENTION, 2),
                                ]);
                            }

                            return __('First APDS (Salary loan 1): maximum ₱:max. ₱:cbu accrues to share capital from net proceeds.', [
                                'max' => number_format(ApdsRules::FIRST_APDS_MAX_AMOUNT, 2),
                                'cbu' => number_format(ApdsRules::FIRST_APDS_CAPITAL_BUILD_UP, 2),
                            ]);
                        })
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (callable $set, callable $get) => $this->syncRegularInstallment($set, $get))
                ),
                TextInput::make('loan_period_months')
                    ->label(__('Repayment period'))
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_REGULAR)
                    ->integer()
                    ->minValue(fn (): int => ApdsRules::FIRST_APDS_MIN_TERM_MONTHS)
                    ->maxValue(fn (): int => ApdsRules::APDS_MAX_TERM_MONTHS)
                    ->suffix(__('month(s)'))
                    ->helperText(function (callable $get): string {
                        if (ApdsRules::isSecondAccountType((string) ($get('loan_type') ?? ''))) {
                            return __('Second APDS: one (1) to seven (7) years (12–84 months). Other charges 6.00%. Age caps if DOB is set: 4yr≤56, 5yr≤55, 6yr≤54, 7yr≤53.');
                        }

                        return __('First APDS: one (1) to seven (7) years (12–84 months). Other charges 5.25% (1–5 years) or 6.00% (6–7 years). Age caps if DOB is set: 4yr≤56, 5yr≤55, 6yr≤54, 7yr≤53.');
                    })
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (callable $set, callable $get) => $this->syncRegularInstallment($set, $get)),
                PesoInput::decorate(
                    TextInput::make('installment_amount')
                        ->label(__('Monthly installment (PHP)'))
                        ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_REGULAR)
                        ->minValue(0)
                        ->helperText(__('Auto-calculated from APDS declining-balance schedule. You may adjust if needed.'))
                ),
                DatePicker::make('first_payment_due_date')
                    ->label(__('First payment due on'))
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_REGULAR)
                    ->native(false),
                DatePicker::make('applicant_signed_at')
                    ->label(__('Date'))
                    ->required()
                    ->native(false),
            ])
            ->columns(2)
            ->visible(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_REGULAR)
            ->columnSpanFull();
    }

    protected function quickLoanSection(): Section
    {
        return Section::make(__('Quick loan application form'))
            ->schema([
                TextInput::make('applicant_name')
                    ->label(__('Full name'))
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK)
                    ->maxLength(255)
                    ->columnSpanFull(),
                DatePicker::make('quick_date_of_birth')
                    ->label(__('Date of birth'))
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK)
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function (?string $state, callable $set): void {
                        $set('quick_age', filled($state) ? Carbon::parse($state)->age : null);
                    }),
                TextInput::make('quick_age')
                    ->label(__('Age'))
                    ->integer()
                    ->minValue(1)
                    ->maxValue(120)
                    ->disabled()
                    ->dehydrated()
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK),
                Radio::make('quick_sex')
                    ->label(__('Sex'))
                    ->options([
                        'male' => __('Male'),
                        'female' => __('Female'),
                    ])
                    ->inline()
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK)
                    ->columnSpanFull(),
                Radio::make('quick_civil_status')
                    ->label(__('Civil status'))
                    ->options([
                        'single' => __('Single'),
                        'married' => __('Married'),
                        'widowed' => __('Widowed'),
                        'separated' => __('Separated'),
                    ])
                    ->inline()
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK)
                    ->columnSpanFull(),
                Textarea::make('applicant_address')
                    ->label(__('Address'))
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK)
                    ->rows(2)
                    ->columnSpanFull(),
                TextInput::make('quick_contact_number')
                    ->label(__('Contact number'))
                    ->tel()
                    ->maxLength(32)
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK),
                TextInput::make('quick_email')
                    ->label(__('Email'))
                    ->email()
                    ->maxLength(255)
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK),
                TextInput::make('quick_occupation')
                    ->label(__('Occupation / Position'))
                    ->maxLength(255)
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK),
                TextInput::make('quick_employer_department')
                    ->label(__('Employer / Department'))
                    ->maxLength(255)
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK),
                TextInput::make('loan_type')
                    ->label(__('Type of loan'))
                    ->default('QUICK LOAN')
                    ->readOnly()
                    ->dehydrated()
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK)
                    ->columnSpanFull(),
                PesoInput::decorate(
                    TextInput::make('loan_amount')
                        ->label(__('Amount to borrow (Maximum ₱2,000)'))
                        ->minValue(1)
                        ->maxValue(QuickLoanLedgerEntries::MAX_AMOUNT)
                        ->rule('max:'.QuickLoanLedgerEntries::MAX_AMOUNT)
                        ->validationAttribute(__('amount to borrow'))
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (mixed $state, callable $set, callable $get): void {
                            $this->syncQuickPayable($set, $get);
                        })
                        ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK)
                ),
                TextInput::make('loan_period_months')
                    ->label(__('Term'))
                    ->numeric()
                    ->default(1)
                    ->readOnly()
                    ->suffix(__('month'))
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK),
                PesoInput::decorate(
                    TextInput::make('installment_amount')
                        ->label(__('Total amount to be paid'))
                        ->minValue(0)
                        ->readOnly()
                        ->dehydrated()
                        ->helperText(__('Automatically set to 1% interest. Collect this on Invoice; the loan amount on Official Receipt.'))
                        ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK)
                ),
                DatePicker::make('first_payment_due_date')
                    ->label(__('Due date'))
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK)
                    ->native(false)
                    ->readOnly()
                    ->dehydrated(),
                DatePicker::make('applicant_signed_at')
                    ->label(__('Date'))
                    ->required()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function (mixed $state, callable $set, callable $get): void {
                        $this->syncQuickDueDate($set, $get);
                    }),
            ])
            ->columns(2)
            ->visible(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK)
            ->columnSpanFull();
    }

    protected function characterLoanSection(): Section
    {
        return Section::make(__('Character loan application form'))
            ->description(__('Complete all fields below. I hereby certify that all statements made hereon are true and complete.'))
            ->schema([
                TextInput::make('applicant_name')
                    ->label(__('Name'))
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_CHARACTER)
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('applicant_address')
                    ->label(__('Address'))
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_CHARACTER)
                    ->rows(2)
                    ->columnSpanFull(),
                Select::make('character_loan_type')
                    ->label(__('Type of loan'))
                    ->options(function (): array {
                        $member = auth()->user();

                        return LoanTypes::characterOptions($member instanceof Member ? $member : null);
                    })
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_CHARACTER)
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function (?string $state, callable $set, callable $get): void {
                        $this->syncCharacterDefaults($set, $get);

                        if (LoanTypes::isCollateralized((string) $state)) {
                            $this->mountAction('collateralizedRequirements');
                        }
                    })
                    ->helperText(function (callable $get): ?string {
                        $type = (string) ($get('character_loan_type') ?? '');
                        $member = auth()->user();
                        $characterRate = $member instanceof Member && $member->isRetiree() ? '1%' : '2%';

                        return match (true) {
                            LoanTypes::isCharacter($type) => $member instanceof Member && $member->isRetiree()
                                ? __('1% monthly, prepaid. Term 1 to 24 months.')
                                : __('2% monthly, prepaid. Term 1 to 12 months.'),
                            LoanTypes::isCharacterShortTerm($type) => __(':rate monthly diminishing. Interest deducted from proceeds. 7-day grace. Equal monthly amortization. Term 1 to 12 months.', [
                                'rate' => $characterRate,
                            ]),
                            LoanTypes::isCalamity($type) => __('Immediate assistance after a calamity. Maximum ₱40,000. 5% per annum. Term 24 months (NTHP).'),
                            LoanTypes::isEmergency($type) => __('Term 3 months. 1% per month prepaid from proceeds. Renewable up to two (2) times. 7-day grace. Repayment every 3 months.'),
                            LoanTypes::isTravel($type) => __('Yazrock Travel and Tours only. Maximum ₱70,000. 2% regular / 1% retiree. Term 6 months. Not convertible to cash.'),
                            LoanTypes::isRetireeShortTerm($type) => __('Retirees with share capital. 12–24 months. 1% monthly diminishing. Optional prepaid interest. 7-day grace. Max 90% of share capital.'),
                            LoanTypes::isCollateralized($type) => __('Maximum ₱300,000. 12 to 24 months. Equal monthly amortization on a diminishing balance. Insurance is required.'),
                            default => null,
                        };
                    })
                    ->columnSpanFull(),
                Actions::make([
                    Action::make('viewCollateralizedRequirements')
                        ->label(__('View requirements'))
                        ->color('gray')
                        ->modalHeading(__('Collateralized loan'))
                        ->modalDescription(__('Please review the documentary requirements and loan terms.'))
                        ->modalContent(function (): HtmlString {
                            /** @var Member $member */
                            $member = auth()->user();

                            return new HtmlString(
                                view('filament.user.collateralized-loan-requirements', [
                                    'member' => $member,
                                ])->render()
                            );
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel(__('Close')),
                ])
                    ->visible(fn (callable $get): bool => LoanTypes::isCollateralized((string) ($get('character_loan_type') ?? '')))
                    ->columnSpanFull(),
                PesoInput::decorate(
                    TextInput::make('loan_amount')
                        ->label(__('Loan amount (PHP)'))
                        ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_CHARACTER)
                        ->minValue(1)
                        ->maxValue(function (callable $get): ?float {
                            if (LoanTypes::isCollateralized((string) ($get('character_loan_type') ?? ''))) {
                                return CollateralizedLoanRules::MAX_AMOUNT;
                            }

                            return null;
                        })
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (callable $set, callable $get) => $this->syncCharacterDefaults($set, $get))
                        ->helperText(function (callable $get): string {
                            $type = (string) ($get('character_loan_type') ?? '');
                            $member = auth()->user();

                            if (LoanTypes::isCollateralized($type)) {
                                return __('Collateralized loan: maximum ₱:max. Equal monthly amortization on a diminishing balance.', [
                                    'max' => number_format(CollateralizedLoanRules::MAX_AMOUNT, 2),
                                ]);
                            }

                            if ((LoanTypes::isCharacter($type) || LoanTypes::isCharacterShortTerm($type)) && $member instanceof Member) {
                                if (ApdsRules::hasQualifyingApdsForCharacter($member)) {
                                    return __('Existing APDS of at least ₱300,000: maximum ₱:max.', [
                                        'max' => number_format(CharacterLoanRules::CHARACTER_WITH_APDS_MAX, 2),
                                    ]);
                                }

                                return __('No APDS loan: 90% of share capital or ₱:max, whichever is lower. Share capital is not on file, so ₱:max is used.', [
                                    'max' => number_format(CharacterLoanRules::CHARACTER_WITHOUT_APDS_MAX, 2),
                                ]);
                            }

                            return '';
                        })
                ),
                TextInput::make('loan_period_months')
                    ->label(__('Term'))
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_CHARACTER)
                    ->integer()
                    ->minValue(function (callable $get): int {
                        $type = (string) ($get('character_loan_type') ?? '');

                        if (LoanTypes::isCollateralized($type)) {
                            return CollateralizedLoanRules::MIN_TERM_MONTHS;
                        }

                        if (LoanTypes::isRetireeShortTerm($type)) {
                            return CharacterLoanRules::RETIREE_SHORT_TERM_MIN_MONTHS;
                        }

                        if (LoanTypes::isCharacter($type) || LoanTypes::isCharacterShortTerm($type)) {
                            return CharacterLoanRules::CHARACTER_TERM_MIN_MONTHS;
                        }

                        return 1;
                    })
                    ->maxValue(function (callable $get): int {
                        $type = (string) ($get('character_loan_type') ?? '');
                        $member = auth()->user();

                        if (LoanTypes::isCollateralized($type)) {
                            return CollateralizedLoanRules::MAX_TERM_MONTHS;
                        }

                        if (LoanTypes::isRetireeShortTerm($type)) {
                            return CharacterLoanRules::RETIREE_SHORT_TERM_MAX_MONTHS;
                        }

                        if (LoanTypes::isCharacter($type) || LoanTypes::isCharacterShortTerm($type)) {
                            return CharacterLoanRules::characterTermMaxMonths($member instanceof Member ? $member : null);
                        }

                        return 84;
                    })
                    ->default(24)
                    ->suffix(__('month(s)'))
                    ->disabled(fn (callable $get): bool => CharacterLoanRules::termIsLocked(
                        (string) ($get('character_loan_type') ?? '')
                    ))
                    ->dehydrated()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (callable $set, callable $get) => $this->syncCharacterDefaults($set, $get))
                    ->helperText(function (callable $get): string {
                        $type = (string) ($get('character_loan_type') ?? '');
                        $member = auth()->user();

                        return match (true) {
                            LoanTypes::isCharacter($type) => $member instanceof Member && $member->isRetiree()
                                ? __('1 to 24 months.')
                                : __('1 to 12 months.'),
                            LoanTypes::isCharacterShortTerm($type) => __('1 to 12 months.'),
                            LoanTypes::isCalamity($type) => __('Fixed at 24 months.'),
                            LoanTypes::isEmergency($type) => __('Fixed at 3 months.'),
                            LoanTypes::isTravel($type) => __('Fixed at 6 months.'),
                            LoanTypes::isRetireeShortTerm($type) => __('12 to 24 months.'),
                            LoanTypes::isCollateralized($type) => __('Collateralized loan: 12 to 24 months. Insurance is required.'),
                            default => '',
                        };
                    }),
                PesoInput::decorate(
                    TextInput::make('installment_amount')
                        ->label(__('Period interest (PHP)'))
                        ->helperText(function (callable $get): string {
                            $type = (string) ($get('character_loan_type') ?? '');
                            $member = auth()->user();
                            $characterRate = $member instanceof Member && $member->isRetiree() ? '1%' : '2%';

                            return match (true) {
                                LoanTypes::isCharacter($type), LoanTypes::isCharacterEmergency($type) => __('Prepaid: :rate monthly × the term in months.', [
                                    'rate' => $characterRate,
                                ]),
                                LoanTypes::isCharacterShortTerm($type) => __(':rate monthly on diminishing balance (first period). Interest deducted from proceeds.', [
                                    'rate' => $characterRate,
                                ]),
                                LoanTypes::isCalamity($type) => __('5% per annum × term in years (e.g. ₱40,000 × 10% for 24 months).'),
                                LoanTypes::isEmergency($type) => __('Prepaid: 1% per month × 3 months, deducted from proceeds.'),
                                LoanTypes::isTravel($type) => __('2% of principal (regular) or 1% (retiree).'),
                                LoanTypes::isRetireeShortTerm($type) => __('1% monthly on diminishing balance (optional prepaid).'),
                                LoanTypes::isCollateralized($type) => __('Auto-calculated at 2% monthly (1% retiree) on a diminishing balance (equal monthly amortization).'),
                                default => '',
                            };
                        })
                        ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_CHARACTER)
                        ->minValue(0)
                ),
                DatePicker::make('first_payment_due_date')
                    ->label(__('First payment due on'))
                    ->helperText(__('Three months after the loan date (e.g. 9-15-2026 → 12-15-2026).'))
                    ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_CHARACTER)
                    ->native(false),
                DatePicker::make('applicant_signed_at')
                    ->label(__('Date'))
                    ->required()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function (?string $state, callable $set, callable $get): void {
                        $this->syncCharacterDueDate($set, $get);
                    }),
            ])
            ->columns(2)
            ->visible(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_CHARACTER)
            ->columnSpanFull();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('submit')
                    ->footer([
                        Actions::make([
                            Action::make('submit')
                                ->label(__('Submit application'))
                                ->submit('submit'),
                        ]),
                    ]),
            ]);
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $applicationType = $data['loan_application_type'] ?? self::APPLICATION_TYPE_REGULAR;
        $isQuickLoan = $applicationType === self::APPLICATION_TYPE_QUICK;
        $isCharacterLoan = $applicationType === self::APPLICATION_TYPE_CHARACTER;
        $isRegularLoan = $applicationType === self::APPLICATION_TYPE_REGULAR;

        /** @var Member $member */
        $member = auth()->user();

        if ($isRegularLoan) {
            $dob = filled($data['date_of_birth'] ?? null)
                ? Carbon::parse($data['date_of_birth'])
                : $member->date_of_birth;

            $ageError = ApdsRules::ageRequirementError(
                $dob,
                (int) ($data['loan_period_months'] ?? 0),
            );

            if ($ageError !== null) {
                Notification::make()
                    ->title(__('APDS age requirement'))
                    ->body($ageError)
                    ->danger()
                    ->send();

                return;
            }

            $amountError = ApdsRules::firstApdsAmountError(
                $member,
                PesoInput::parse($data['loan_amount'] ?? 0),
                (string) ($data['loan_type'] ?? LoanTypes::REGULAR),
            );

            if ($amountError !== null) {
                Notification::make()
                    ->title(__('Maximum loanable amount'))
                    ->body($amountError)
                    ->danger()
                    ->send();

                return;
            }

            $termError = ApdsRules::firstApdsTermError(
                $member,
                (int) ($data['loan_period_months'] ?? 0),
                (string) ($data['loan_type'] ?? LoanTypes::REGULAR),
            );

            if ($termError !== null) {
                Notification::make()
                    ->title(__('APDS loan term'))
                    ->body($termError)
                    ->danger()
                    ->send();

                return;
            }

            $restructureError = ApdsRules::restructureAggregateError(
                $member,
                PesoInput::parse($data['loan_amount'] ?? 0),
                ($data['loan_category'] ?? null) === LoanCategory::Restructure->value,
            );

            if ($restructureError !== null) {
                Notification::make()
                    ->title(__('Maximum loanable amount'))
                    ->body($restructureError)
                    ->danger()
                    ->send();

                return;
            }
        }

        if ($isCharacterLoan) {
            $characterType = (string) ($data['character_loan_type'] ?? '');
            $characterError = CharacterLoanRules::applicationError(
                $member,
                $characterType,
                PesoInput::parse($data['loan_amount'] ?? 0),
                (int) ($data['loan_period_months'] ?? 0),
            );

            if ($characterError !== null) {
                Notification::make()
                    ->title(__('Character loan'))
                    ->body($characterError)
                    ->danger()
                    ->send();

                return;
            }
        }

        $memberUpdates = array_filter([
            'name' => $data['applicant_name'] ?? null,
            'address' => $data['applicant_address'] ?? null,
        ], fn (mixed $value): bool => filled($value));

        if ($isRegularLoan && filled($data['date_of_birth'] ?? null)) {
            $memberUpdates['date_of_birth'] = $data['date_of_birth'];
        }

        if ($isQuickLoan) {
            $memberUpdates = array_merge($memberUpdates, array_filter([
                'email' => $data['quick_email'] ?? null,
                'date_of_birth' => $data['quick_date_of_birth'] ?? null,
                'sex' => $data['quick_sex'] ?? null,
                'civil_status' => $data['quick_civil_status'] ?? null,
                'contact_number' => $data['quick_contact_number'] ?? null,
                'occupation' => $data['quick_occupation'] ?? null,
                'employer_department' => $data['quick_employer_department'] ?? null,
            ], fn (mixed $value): bool => filled($value)));
        }

        if ($memberUpdates !== []) {
            $member->update($memberUpdates);
        }

        $loanType = match ($applicationType) {
            self::APPLICATION_TYPE_QUICK => LoanTypes::QUICK,
            self::APPLICATION_TYPE_CHARACTER => (string) ($data['character_loan_type'] ?? LoanTypes::CHARACTER),
            default => in_array((string) ($data['loan_type'] ?? ''), [
                LoanTypes::REGULAR,
                LoanTypes::SALARY_2,
            ], true)
                ? (string) $data['loan_type']
                : LoanTypes::REGULAR,
        };

        $isSecondApds = $isRegularLoan && ApdsRules::isSecondAccountType($loanType);
        $installmentAmount = PesoInput::parse($data['installment_amount'] ?? 0);
        $firstPaymentDueDate = $data['first_payment_due_date'] ?? null;

        if ($isQuickLoan) {
            $amount = PesoInput::parse($data['loan_amount'] ?? 0);

            if ($amount > QuickLoanLedgerEntries::MAX_AMOUNT) {
                Notification::make()
                    ->title(__('Maximum loanable amount'))
                    ->body(__('Quick loans may not exceed ₱:max.', [
                        'max' => number_format(QuickLoanLedgerEntries::MAX_AMOUNT, 2),
                    ]))
                    ->danger()
                    ->send();

                return;
            }

            $installmentAmount = QuickLoanLedgerEntries::interestOn($amount);
            $signedAt = $data['applicant_signed_at'] ?? now()->toDateString();
            $firstPaymentDueDate = Carbon::parse($signedAt)->addMonthNoOverflow()->toDateString();
        }

        if ($isRegularLoan) {
            $schedule = RegularLoanSchedule::calculate(
                PesoInput::parse($data['loan_amount']),
                (int) $data['loan_period_months'],
                $isSecondApds,
            );
            $installmentAmount = $schedule->monthlyInstallment;
        }

        if ($isCharacterLoan) {
            $installmentAmount = CharacterLoanRules::periodInterest(
                $loanType,
                PesoInput::parse($data['loan_amount'] ?? 0),
                (int) ($data['loan_period_months'] ?? 0),
                $member->isRetiree(),
            );
            $signedAt = $data['applicant_signed_at'] ?? now()->toDateString();
            $firstPaymentDueDate = Carbon::parse($signedAt)->addMonthsNoOverflow(3)->toDateString();
            $data['applicant_signed_at'] = $signedAt;
        }

        $data['loan_type'] = $loanType;
        $data['loan_period_months'] = $isQuickLoan ? 1 : ($data['loan_period_months'] ?? null);
        $data['installment_amount'] = $installmentAmount;
        $data['first_payment_due_date'] = $firstPaymentDueDate;
        $data['applicant_signed_at'] = $data['applicant_signed_at'] ?? now()->toDateString();
        $data['loan_date'] = $isCharacterLoan
            ? ($data['applicant_signed_at'] ?? now()->toDateString())
            : now()->toDateString();

        if ($isCharacterLoan) {
            $data['character_loan_type'] = $data['character_loan_type'] ?? $loanType;
        }

        try {
            app(LoanApplicationService::class)->submit($member, $data);
        } catch (LoanApplicationException $exception) {
            Notification::make()
                ->title($exception->title)
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?? $exception->getMessage();

            Notification::make()
                ->title(__('Application not submitted'))
                ->body($message)
                ->danger()
                ->send();

            throw $exception;
        }

        Notification::make()
            ->title(__('Confirm the application from your email'))
            ->body(__('We sent a confirmation link to :email. TEMPUCO can review and approve the loan only after you confirm it.', [
                'email' => $member->email,
            ]))
            ->success()
            ->send();

        $this->redirect(UserDashboard::getUrl());
    }

    /**
     * @param  callable(string, mixed): void  $set
     * @param  callable(string): mixed  $get
     */
    private function syncRegularInstallment(callable $set, callable $get): void
    {
        if ($get('loan_application_type') !== self::APPLICATION_TYPE_REGULAR) {
            return;
        }

        $amount = PesoInput::parse($get('loan_amount'));
        $months = (int) ($get('loan_period_months') ?? 0);

        if ($amount <= 0 || $months < 1) {
            return;
        }

        $schedule = RegularLoanSchedule::calculate(
            $amount,
            $months,
            ApdsRules::isSecondAccountType((string) ($get('loan_type') ?? '')),
        );

        $set('installment_amount', number_format($schedule->monthlyInstallment, 2, '.', ','));
    }

    /**
     * @param  callable(string, mixed): void  $set
     * @param  callable(string): mixed  $get
     */
    private function syncCharacterDefaults(callable $set, callable $get): void
    {
        if ($get('loan_application_type') !== self::APPLICATION_TYPE_CHARACTER) {
            return;
        }

        $type = (string) ($get('character_loan_type') ?: LoanTypes::CHARACTER);

        if (CharacterLoanRules::termIsLocked($type)) {
            $set('loan_period_months', CharacterLoanRules::defaultTermMonths($type));
        } elseif (LoanTypes::isCharacter($type) || LoanTypes::isCharacterShortTerm($type)) {
            $member = auth()->user();
            $months = (int) ($get('loan_period_months') ?? 0);
            $max = CharacterLoanRules::characterTermMaxMonths($member instanceof Member ? $member : null);

            if ($months < CharacterLoanRules::CHARACTER_TERM_MIN_MONTHS) {
                $set('loan_period_months', CharacterLoanRules::defaultTermMonths($type));
            } elseif ($months > $max) {
                $set('loan_period_months', $max);
            }
        } elseif (LoanTypes::isRetireeShortTerm($type) || LoanTypes::isCollateralized($type)) {
            $months = (int) ($get('loan_period_months') ?? 0);
            $min = LoanTypes::isCollateralized($type)
                ? CollateralizedLoanRules::MIN_TERM_MONTHS
                : CharacterLoanRules::RETIREE_SHORT_TERM_MIN_MONTHS;
            $max = LoanTypes::isCollateralized($type)
                ? CollateralizedLoanRules::MAX_TERM_MONTHS
                : CharacterLoanRules::RETIREE_SHORT_TERM_MAX_MONTHS;
            if ($months < $min || $months > $max) {
                $set('loan_period_months', $min);
            }
        }

        $this->syncCharacterDueDate($set, $get);

        $amount = PesoInput::parse($get('loan_amount'));
        $months = (int) ($get('loan_period_months') ?? CharacterLoanRules::defaultTermMonths($type));
        $member = auth()->user();

        if ($amount <= 0) {
            return;
        }

        $set('installment_amount', number_format(CharacterLoanRules::periodInterest(
            $type,
            $amount,
            $months,
            $member instanceof Member && $member->isRetiree(),
        ), 2, '.', ','));
    }

    /**
     * @param  callable(string, mixed): void  $set
     * @param  callable(string): mixed  $get
     */
    private function syncQuickPayable(callable $set, callable $get): void
    {
        if ($get('loan_application_type') !== self::APPLICATION_TYPE_QUICK) {
            return;
        }

        $amount = PesoInput::parse($get('loan_amount'));

        if ($amount <= 0 || $amount > QuickLoanLedgerEntries::MAX_AMOUNT) {
            $set('installment_amount', null);

            return;
        }

        $set('installment_amount', number_format(QuickLoanLedgerEntries::interestOn($amount), 2, '.', ','));
    }

    /**
     * @param  callable(string, mixed): void  $set
     * @param  callable(string): mixed  $get
     */
    private function syncQuickDueDate(callable $set, callable $get): void
    {
        if ($get('loan_application_type') !== self::APPLICATION_TYPE_QUICK) {
            return;
        }

        $signedAt = $get('applicant_signed_at');

        if (blank($signedAt)) {
            return;
        }

        $set(
            'first_payment_due_date',
            Carbon::parse((string) $signedAt)->addMonthNoOverflow()->toDateString(),
        );
    }

    /**
     * @param  callable(string, mixed): void  $set
     * @param  callable(string): mixed  $get
     */
    private function syncCharacterDueDate(callable $set, callable $get): void
    {
        if ($get('loan_application_type') !== self::APPLICATION_TYPE_CHARACTER) {
            return;
        }

        $signedAt = $get('applicant_signed_at');

        if (blank($signedAt)) {
            return;
        }

        $set(
            'first_payment_due_date',
            Carbon::parse((string) $signedAt)->addMonthsNoOverflow(3)->toDateString(),
        );
    }

    /**
     * @return array<int, Select|Textarea>
     */
    private function purposeAndPaymentFields(): array
    {
        return [
            Select::make('purpose_of_loan')
                ->label(__('Purpose of loan'))
                ->options(collect(LoanPurpose::cases())->mapWithKeys(
                    fn (LoanPurpose $purpose): array => [$purpose->value => $purpose->getLabel()]
                )->all())
                ->placeholder(__('Select an option'))
                ->required()
                ->live()
                ->native(false)
                ->searchable(false),
            Select::make('mode_of_payment')
                ->label(__('Mode of payment'))
                ->options(collect(ModeOfPayment::cases())->mapWithKeys(
                    fn (ModeOfPayment $mode): array => [$mode->value => $mode->getLabel()]
                )->all())
                ->placeholder(__('Select an option'))
                ->required()
                ->native(false)
                ->searchable(false),
            Textarea::make('purpose_of_loan_other')
                ->label(__('Please specify purpose'))
                ->required(fn (callable $get): bool => $get('purpose_of_loan') === LoanPurpose::Others->value)
                ->visible(fn (callable $get): bool => $get('purpose_of_loan') === LoanPurpose::Others->value)
                ->rows(3)
                ->maxLength(1000)
                ->columnSpanFull(),
        ];
    }
}
