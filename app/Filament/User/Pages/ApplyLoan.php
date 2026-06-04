<?php

namespace App\Filament\User\Pages;

use App\Enums\LoanCategory;
use App\Enums\LoanPurpose;
use App\Enums\LoanStatus;
use App\Enums\ModeOfPayment;
use App\Models\Loan;
use App\Models\User;
use BackedEnum;
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

class ApplyLoan extends Page
{
    private const APPLICATION_TYPE_REGULAR = 'regular';

    private const APPLICATION_TYPE_QUICK = 'quick';

    protected static ?string $navigationLabel = 'Loan application';

    protected static ?string $title = 'Loan application';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentPlus;

    protected static ?int $navigationSort = -1;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $this->form->fill([
            'loan_application_type' => self::APPLICATION_TYPE_REGULAR,
            'applicant_name' => $user->name,
            'applicant_address' => $user->address,
            'applicant_signed_at' => now()->toDateString(),
            'quick_contact_number' => $user->cellphone,
            'quick_email' => $user->email,
            'loan_period_months' => 1,
            'loan_type' => 'QUICK LOAN',
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Loan application');
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

                Section::make(__('Loan application type'))
                    ->schema([
                        Radio::make('loan_application_type')
                            ->label(__('Choose loan form'))
                            ->options([
                                self::APPLICATION_TYPE_REGULAR => __('Regular loan'),
                                self::APPLICATION_TYPE_QUICK => __('Quick loan'),
                            ])
                            ->inline()
                            ->live()
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make(__('Loan category'))
                    ->schema([
                        Radio::make('loan_category')
                            ->label(__('Select loan category'))
                            ->options(collect(LoanCategory::cases())->mapWithKeys(
                                fn (LoanCategory $category): array => [$category->value => $category->getLabel()]
                            )->all())
                            ->inline()
                            ->required(fn (callable $get): bool => $get('loan_application_type') !== self::APPLICATION_TYPE_QUICK)
                            ->visible(fn (callable $get): bool => $get('loan_application_type') !== self::APPLICATION_TYPE_QUICK)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make(__('Purpose and payment'))
                    ->schema($this->purposeAndPaymentFields())
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(__('Applicant\'s request (Regular loan)'))
                    ->description(__('Complete all fields below. I hereby certify that all statements made hereon are true and complete.'))
                    ->schema([
                        TextInput::make('applicant_name')
                            ->label(__('Name'))
                            ->required(fn (callable $get): bool => $get('loan_application_type') !== self::APPLICATION_TYPE_QUICK)
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('applicant_address')
                            ->label(__('Address'))
                            ->required(fn (callable $get): bool => $get('loan_application_type') !== self::APPLICATION_TYPE_QUICK)
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('loan_type')
                            ->label(__('Type of loan'))
                            ->placeholder(__('e.g. Emergency, Educational'))
                            ->required(fn (callable $get): bool => $get('loan_application_type') !== self::APPLICATION_TYPE_QUICK)
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('loan_amount')
                            ->label(__('Loan amount (PHP)'))
                            ->required(fn (callable $get): bool => $get('loan_application_type') !== self::APPLICATION_TYPE_QUICK)
                            ->numeric()
                            ->minValue(1)
                            ->step(0.01)
                            ->prefix('₱'),
                        TextInput::make('loan_period_months')
                            ->label(__('Repayment period'))
                            ->required(fn (callable $get): bool => $get('loan_application_type') !== self::APPLICATION_TYPE_QUICK)
                            ->integer()
                            ->minValue(1)
                            ->suffix(__('month(s)')),
                        TextInput::make('installment_amount')
                            ->label(__('Monthly installment (PHP)'))
                            ->required(fn (callable $get): bool => $get('loan_application_type') !== self::APPLICATION_TYPE_QUICK)
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('₱'),
                        DatePicker::make('first_payment_due_date')
                            ->label(__('First payment due on'))
                            ->required(fn (callable $get): bool => $get('loan_application_type') !== self::APPLICATION_TYPE_QUICK)
                            ->native(false),
                        DatePicker::make('applicant_signed_at')
                            ->label(__('Date'))
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2)
                    ->visible(fn (callable $get): bool => $get('loan_application_type') !== self::APPLICATION_TYPE_QUICK)
                    ->columnSpanFull(),

                Section::make(__('Quick loan application form'))
                    ->schema([
                        TextInput::make('applicant_name')
                            ->label(__('Full name'))
                            ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK)
                            ->maxLength(255)
                            ->columnSpanFull(),
                        DatePicker::make('quick_date_of_birth')
                            ->label(__('Date of birth'))
                            ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK)
                            ->native(false),
                        TextInput::make('quick_age')
                            ->label(__('Age'))
                            ->integer()
                            ->minValue(1)
                            ->maxValue(120)
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
                        TextInput::make('loan_amount')
                            ->label(__('Amount to borrow (Maximum ₱2,000)'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(2000)
                            ->step(0.01)
                            ->prefix('₱')
                            ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK),
                        TextInput::make('loan_period_months')
                            ->label(__('Term'))
                            ->numeric()
                            ->default(1)
                            ->readOnly()
                            ->suffix(__('month'))
                            ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK),
                        TextInput::make('installment_amount')
                            ->label(__('Total amount to be paid'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('₱')
                            ->helperText(__('Set this to loan amount + 1% interest.'))
                            ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK),
                        DatePicker::make('first_payment_due_date')
                            ->label(__('Due date'))
                            ->required(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK)
                            ->native(false),
                        DatePicker::make('applicant_signed_at')
                            ->label(__('Date'))
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2)
                    ->visible(fn (callable $get): bool => $get('loan_application_type') === self::APPLICATION_TYPE_QUICK)
                    ->columnSpanFull(),
            ]);
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
        $isQuickLoan = ($data['loan_application_type'] ?? self::APPLICATION_TYPE_REGULAR) === self::APPLICATION_TYPE_QUICK;

        /** @var User $user */
        $user = auth()->user();
        $user->update(array_filter([
            'name' => $data['applicant_name'] ?? null,
            'address' => $data['applicant_address'] ?? null,
            'cellphone' => $isQuickLoan ? ($data['quick_contact_number'] ?? null) : null,
            'email' => $isQuickLoan ? ($data['quick_email'] ?? null) : null,
        ], fn (mixed $value): bool => filled($value)));

        $loanType = $isQuickLoan
            ? 'QUICK LOAN'
            : ($data['loan_type'] ?? '');

        Loan::query()->create([
            'user_id' => auth()->id(),
            'status' => LoanStatus::Pending,
            'loan_category' => $isQuickLoan ? LoanCategory::AdditionalNew : $data['loan_category'],
            'loan_type' => $loanType,
            'loan_amount' => $data['loan_amount'],
            'loan_period_months' => $data['loan_period_months'],
            'installment_amount' => $data['installment_amount'],
            'first_payment_due_date' => $data['first_payment_due_date'] ?? null,
            'purpose_of_loan' => $isQuickLoan ? null : $data['purpose_of_loan'],
            'purpose_of_loan_other' => $isQuickLoan ? null : (
                ($data['purpose_of_loan'] ?? null) === LoanPurpose::Others->value
                    ? ($data['purpose_of_loan_other'] ?? null)
                    : null
            ),
            'application_notes' => $isQuickLoan ? $this->buildQuickLoanPurpose($data) : null,
            'mode_of_payment' => $data['mode_of_payment'],
            'applicant_signed_at' => $data['applicant_signed_at'] ?? now()->toDateString(),
            'loan_date' => now()->toDateString(),
        ]);

        Notification::make()
            ->title(__('Loan application submitted'))
            ->success()
            ->send();

        $this->redirect(UserDashboard::getUrl());
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
                ->required()
                ->live()
                ->native(false)
                ->columnSpanFull(),
            Textarea::make('purpose_of_loan_other')
                ->label(__('Please specify purpose'))
                ->required(fn (callable $get): bool => $get('purpose_of_loan') === LoanPurpose::Others->value)
                ->visible(fn (callable $get): bool => $get('purpose_of_loan') === LoanPurpose::Others->value)
                ->rows(3)
                ->maxLength(1000)
                ->columnSpanFull(),
            Select::make('mode_of_payment')
                ->label(__('Mode of payment'))
                ->options(collect(ModeOfPayment::cases())->mapWithKeys(
                    fn (ModeOfPayment $mode): array => [$mode->value => $mode->getLabel()]
                )->all())
                ->required()
                ->native(false)
                ->columnSpanFull(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function formatLoanPurposeSummary(array $data): string
    {
        $purposeLabel = LoanPurpose::tryFrom((string) ($data['purpose_of_loan'] ?? ''))?->getLabel()
            ?? (string) ($data['purpose_of_loan'] ?? '');

        if (($data['purpose_of_loan'] ?? null) === LoanPurpose::Others->value && filled($data['purpose_of_loan_other'] ?? null)) {
            return $purposeLabel.': '.($data['purpose_of_loan_other']);
        }

        return $purposeLabel;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buildQuickLoanPurpose(array $data): string
    {
        $paymentLabel = ModeOfPayment::tryFrom((string) ($data['mode_of_payment'] ?? ''))?->getLabel()
            ?? (string) ($data['mode_of_payment'] ?? 'N/A');

        $details = [
            'Purpose of loan: '.$this->formatLoanPurposeSummary($data),
            'Mode of payment: '.$paymentLabel,
            '',
            'Quick Loan Applicant Details:',
            'Address: '.($data['applicant_address'] ?? 'N/A'),
            'Date of birth: '.($data['quick_date_of_birth'] ?? 'N/A'),
            'Age: '.($data['quick_age'] ?? 'N/A'),
            'Sex: '.ucfirst((string) ($data['quick_sex'] ?? 'N/A')),
            'Civil status: '.ucfirst((string) ($data['quick_civil_status'] ?? 'N/A')),
            'Contact number: '.($data['quick_contact_number'] ?? 'N/A'),
            'Email: '.($data['quick_email'] ?? 'N/A'),
            'Occupation / Position: '.($data['quick_occupation'] ?? 'N/A'),
            'Employer / Department: '.($data['quick_employer_department'] ?? 'N/A'),
        ];

        return implode(PHP_EOL, $details);
    }
}
