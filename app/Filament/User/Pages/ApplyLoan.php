<?php

namespace App\Filament\User\Pages;

use App\Enums\LoanStatus;
use App\Models\Loan;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ApplyLoan extends Page
{
    protected static ?string $navigationLabel = 'Fill up loan';

    protected static ?string $title = 'Fill up loan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentPlus;

    protected static ?int $navigationSort = -1;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'loan_date' => now()->toDateString(),
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Fill up loan');
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
                TextInput::make('apply_loan')
                    ->label(__('Apply loan'))
                    ->placeholder(__('e.g. Personal loan, Emergency fund'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('loan_amount')
                    ->label(__('Loan amount'))
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->step(0.01),
                TextInput::make('loan_period_months')
                    ->label(__('Loan period'))
                    ->required()
                    ->integer()
                    ->minValue(1)
                    ->suffix(__('months')),
                TextInput::make('installment_amount')
                    ->label(__('Installment amount'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01),
                DatePicker::make('loan_date')
                    ->label(__('Date'))
                    ->required()
                    ->native(false)
                    ->default(now()),
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
            'apply_loan' => $data['apply_loan'],
            'loan_amount' => $data['loan_amount'],
            'loan_period_months' => $data['loan_period_months'],
            'installment_amount' => $data['installment_amount'],
            'loan_date' => $data['loan_date'],
        ]);

        Notification::make()
            ->title(__('Loan application submitted'))
            ->success()
            ->send();

        $this->redirect(UserDashboard::getUrl());
    }
}
