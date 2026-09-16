<?php

namespace App\Filament\Resources\Loans;

use App\Filament\Resources\Loans\Pages\EditLoan;
use App\Filament\Resources\Loans\Pages\ListLoans;
use App\Filament\Resources\Loans\Pages\ViewLoan;
use App\Filament\Resources\Loans\Schemas\LoanForm;
use App\Filament\Resources\Loans\Schemas\LoanInfolist;
use App\Filament\Resources\Loans\Tables\LoansTable;
use App\Models\RegularLoan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LoanResource extends Resource
{
    protected static ?string $model = RegularLoan::class;

    protected static ?string $slug = 'regular-loans';

    protected static ?string $recordTitleAttribute = 'loan_type';

    protected static ?string $modelLabel = 'regular loan';

    protected static ?string $pluralModelLabel = 'regular loans';

    protected static ?string $navigationLabel = 'Regular loans';

    protected static string|UnitEnum|null $navigationGroup = 'Loans';

    protected static ?int $navigationSort = 20;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoans::route('/'),
            'view' => ViewLoan::route('/{record}'),
            'edit' => EditLoan::route('/{record}/edit'),
        ];
    }
}
