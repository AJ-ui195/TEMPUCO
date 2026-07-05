<?php

namespace App\Filament\Pos\Pages;

use App\Filament\Pos\Concerns\InteractsWithInventoryPanel;
use App\Models\PosBranch;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;

class BranchesPage extends Page
{
    use InteractsWithInventoryPanel;
    protected static ?string $navigationLabel = 'Branches';

    protected static ?string $title = 'Branches';

    protected static ?string $slug = 'branches';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Branches');
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            $this->createBranchAction(),
        ];
    }

    public function createBranchAction(): Action
    {
        return Action::make('createBranch')
            ->label(__('Add branch'))
            ->icon(Heroicon::OutlinedPlus)
            ->modalHeading(__('Add branch'))
            ->form($this->branchFormSchema())
            ->action(function (array $data): void {
                PosBranch::query()->create($data);

                Notification::make()
                    ->title(__('Branch created'))
                    ->success()
                    ->send();
            });
    }

    public function editBranchAction(): Action
    {
        return Action::make('editBranch')
            ->label(__('Edit'))
            ->icon(Heroicon::OutlinedPencil)
            ->modalHeading(__('Edit branch'))
            ->fillForm(function (array $arguments): array {
                $branch = PosBranch::query()->findOrFail($arguments['branch']);

                return [
                    'branch_number' => $branch->branch_number,
                    'name' => $branch->name,
                    'code' => $branch->code,
                    'phone' => $branch->phone,
                    'branch_holder' => $branch->branch_holder,
                    'is_active' => $branch->is_active,
                    'has_pos' => $branch->has_pos,
                ];
            })
            ->form(function (array $arguments): array {
                return $this->branchFormSchema(
                    PosBranch::query()->findOrFail($arguments['branch']),
                );
            })
            ->action(function (array $data, array $arguments): void {
                PosBranch::query()
                    ->findOrFail($arguments['branch'])
                    ->update($data);

                Notification::make()
                    ->title(__('Branch updated'))
                    ->success()
                    ->send();
            });
    }

    public function viewBranchInventoryAction(): Action
    {
        return Action::make('viewBranchInventory')
            ->label(__('View inventory'))
            ->icon(Heroicon::OutlinedArchiveBox)
            ->url(fn (array $arguments): string => static::inventoryPageUrl(BranchInventoryPage::class, [
                'branch' => $arguments['branch'],
            ]));
    }

    public function viewBranchAction(): Action
    {
        return Action::make('viewBranch')
            ->modalHeading(fn (array $arguments): string => PosBranch::query()
                ->findOrFail($arguments['branch'])
                ->name)
            ->modalContent(fn (array $arguments): View => view(
                'filament.pos.branch-modal',
                ['branch' => PosBranch::query()->findOrFail($arguments['branch'])],
            ))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('Close'))
            ->extraModalFooterActions(fn (array $arguments): array => [
                $this->viewBranchInventoryAction()
                    ->arguments(['branch' => $arguments['branch']]),
                $this->editBranchAction()
                    ->arguments(['branch' => $arguments['branch']]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Branches'))
                    ->schema([
                        TextEntry::make('branches_grid')
                            ->hiddenLabel()
                            ->state(fn (): HtmlString => new HtmlString(
                                view('filament.pos.branches-grid', [
                                    'branches' => PosBranch::query()
                                        ->orderBy('branch_number')
                                        ->get(),
                                ])->render()
                            ))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, TextInput|Toggle>
     */
    protected function branchFormSchema(?PosBranch $branch = null): array
    {
        $nextBranchNumber = (int) (PosBranch::query()->max('branch_number') ?? 0) + 1;

        return [
            TextInput::make('branch_number')
                ->label(__('Branch number'))
                ->required()
                ->integer()
                ->minValue(1)
                ->default($branch?->branch_number ?? $nextBranchNumber)
                ->unique(PosBranch::class, 'branch_number', ignorable: $branch),
            TextInput::make('name')
                ->label(__('Branch name'))
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            TextInput::make('code')
                ->label(__('Branch code'))
                ->required()
                ->maxLength(16)
                ->default($branch?->code ?? 'BR-'.str_pad((string) $nextBranchNumber, 3, '0', STR_PAD_LEFT))
                ->unique(PosBranch::class, 'code', ignorable: $branch),
            TextInput::make('phone')
                ->label(__('Phone'))
                ->tel()
                ->maxLength(32),
            TextInput::make('branch_holder')
                ->label(__('Branch holder'))
                ->maxLength(255),
            Toggle::make('is_active')
                ->label(__('Active'))
                ->default($branch?->is_active ?? true),
            Toggle::make('has_pos')
                ->label(__('POS enabled'))
                ->helperText(__('Turn off for the original / head office branch that does not run a POS terminal.'))
                ->default($branch?->has_pos ?? true),
        ];
    }
}
