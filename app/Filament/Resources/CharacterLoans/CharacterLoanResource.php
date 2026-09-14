<?php

namespace App\Filament\Resources\CharacterLoans;

use App\Filament\Resources\CharacterLoans\Pages\EditCharacterLoan;
use App\Filament\Resources\CharacterLoans\Pages\ListCharacterLoans;
use App\Filament\Resources\CharacterLoans\Pages\ViewCharacterLoan;
use App\Filament\Resources\Loans\Schemas\LoanForm;
use App\Filament\Resources\Loans\Schemas\LoanInfolist;
use App\Filament\Resources\Loans\Tables\LoansTable;
use App\Models\CharacterLoan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CharacterLoanResource extends Resource
{
    protected static ?string $model = CharacterLoan::class;

    protected static ?string $slug = 'character-loans';

    protected static ?string $recordTitleAttribute = 'loan_type';

    protected static ?string $modelLabel = 'character loan';

    protected static ?string $pluralModelLabel = 'character loans';

    protected static ?string $navigationLabel = 'Character loans';

    protected static string|UnitEnum|null $navigationGroup = 'Loans';

    protected static ?int $navigationSort = 22;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

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
            'index' => ListCharacterLoans::route('/'),
            'view' => ViewCharacterLoan::route('/{record}'),
            'edit' => EditCharacterLoan::route('/{record}/edit'),
        ];
    }
}
