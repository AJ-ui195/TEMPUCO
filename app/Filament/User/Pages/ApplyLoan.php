<?php

namespace App\Filament\User\Pages;

use App\Enums\LoanCategory;
use App\Enums\LoanStatus;
use App\Models\Loan;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
            'applicant_name' => $user->name,
            'applicant_address' => $user->address,
            'applicant_signed_at' => now()->toDateString(),
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
                Placeholder::make('form_header')
                    ->hiddenLabel()
                    ->content(fn (): HtmlString => new HtmlString(
                        view('filament.user.loan-application-header')->render()
                    ))
                    ->columnSpanFull(),

                Section::make(__('Loan category'))
                    ->schema([
                        Radio::make('loan_category')
                            ->label(__('Select loan category'))
                            ->options(collect(LoanCategory::cases())->mapWithKeys(
                                fn (LoanCategory $category): array => [$category->value => $category->getLabel()]
                            )->all())
                            ->inline()
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make(__('Applicant\'s request'))
                    ->description(__('Complete all fields below. I hereby certify that all statements made hereon are true and complete.'))
                    ->schema([
                        TextInput::make('applicant_name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('applicant_address')
                            ->label(__('Address'))
                            ->required()
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('loan_type')
                            ->label(__('Type of loan'))
                            ->placeholder(__('e.g. Emergency, Educational'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('loan_amount_words')
                            ->label(__('Loan amount (in words)'))
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('loan_amount')
                            ->label(__('Loan amount (PHP)'))
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->step(0.01)
                            ->prefix('₱'),
                        TextInput::make('loan_period_months')
                            ->label(__('Repayment period'))
                            ->required()
                            ->integer()
                            ->minValue(1)
                            ->suffix(__('month(s)')),
                        TextInput::make('installment_amount')
                            ->label(__('Monthly installment (PHP)'))
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('₱'),
                        DatePicker::make('first_payment_due_date')
                            ->label(__('First payment due on'))
                            ->required()
                            ->native(false),
                        Textarea::make('loan_purpose')
                            ->label(__('Purpose of loan (explain fully)'))
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                        DatePicker::make('applicant_signed_at')
                            ->label(__('Date'))
                            ->required()
                            ->native(false),
                        TextInput::make('applicant_signature_name')
                            ->label(__('Signature over printed name / loan applicant'))
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2)
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

        Loan::query()->create([
            'user_id' => auth()->id(),
            'status' => LoanStatus::Pending,
            'loan_category' => $data['loan_category'],
            'applicant_name' => $data['applicant_name'],
            'applicant_address' => $data['applicant_address'],
            'apply_loan' => $data['loan_type'],
            'loan_type' => $data['loan_type'],
            'loan_amount' => $data['loan_amount'],
            'loan_amount_words' => $data['loan_amount_words'] ?? null,
            'loan_period_months' => $data['loan_period_months'],
            'installment_amount' => $data['installment_amount'],
            'first_payment_due_date' => $data['first_payment_due_date'],
            'loan_purpose' => $data['loan_purpose'],
            'applicant_signed_at' => $data['applicant_signed_at'],
            'applicant_signature_name' => $data['applicant_signature_name'],
            'loan_date' => now()->toDateString(),
        ]);

        Notification::make()
            ->title(__('Loan application submitted'))
            ->success()
            ->send();

        $this->redirect(UserDashboard::getUrl());
    }
}
