<?php

namespace App\Filament\Resources\QuickLoans;

use App\Filament\Resources\Loans\Schemas\LoanForm;
use App\Filament\Resources\Loans\Schemas\LoanInfolist;
use App\Filament\Resources\Loans\Tables\LoansTable;
use App\Filament\Resources\QuickLoans\Pages\EditQuickLoan;
use App\Filament\Resources\QuickLoans\Pages\ListQuickLoans;
use App\Filament\Resources\QuickLoans\Pages\ViewQuickLoan;
use App\Models\QuickLoan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class QuickLoanResource extends Resource
{
    protected static ?string $model = QuickLoan::class;

    protected static ?string $slug = 'quick-loans';

    protected static ?string $recordTitleAttribute = 'loan_type';

    protected static ?string $modelLabel = 'quick loan';

    protected static ?string $pluralModelLabel = 'quick loans';

    protected static ?string $navigationLabel = 'Quick loans';

    protected static string|UnitEnum|null $navigationGroup = 'Loans';

    protected static ?int $navigationSort = 21;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    public static function form(Schema $schema): Schema
    {
        return LoanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LoanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoansTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'certification', 'committeeDecision']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuickLoans::route('/'),
            'view' => ViewQuickLoan::route('/{record}'),
            'edit' => EditQuickLoan::route('/{record}/edit'),
        ];
    }
}
