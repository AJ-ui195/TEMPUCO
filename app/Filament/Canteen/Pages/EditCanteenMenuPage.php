<?php

namespace App\Filament\Canteen\Pages;

use App\Support\CanteenMenu;
use BackedEnum;
use Filament\Actions\Action;
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
use InvalidArgumentException;

class EditCanteenMenuPage extends Page
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    protected static ?string $navigationLabel = 'Edit food choices';

    protected static ?string $title = 'Edit food choices';

    protected static ?string $slug = 'edit-food-choices';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? __('Edit food choices');
    }

    public function mount(): void
    {
        $slots = [];

        foreach (CanteenMenu::choices() as $item) {
            $slots[(int) $item->menu_slot] = [
                'name' => $item->name,
                'unit_price' => number_format((float) $item->unit_price, 2, '.', ''),
                'quantity' => (string) (int) $item->quantity,
            ];
        }

        $this->form->fill(['slots' => $slots]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $fields = [];

        foreach (range(1, CanteenMenu::SLOT_COUNT) as $slot) {
            $fields[] = Section::make(__('Choice :n', ['n' => $slot]))
                ->schema([
                    TextInput::make("slots.{$slot}.name")
                        ->label(__('Food name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make("slots.{$slot}.unit_price")
                        ->label(__('Price (₱)'))
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->prefix('₱'),
                    TextInput::make("slots.{$slot}.quantity")
                        ->label(__('Quantity'))
                        ->required()
                        ->integer()
                        ->minValue(0)
                        ->helperText(__('Shown on Canteen POS. Sales reduce this amount.')),
                ])
                ->columns(2);
        }

        return $schema->components($fields);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('canteen-menu-form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label(__('Save food choices'))
                                ->submit('save'),
                        ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        try {
            CanteenMenu::save($data['slots'] ?? []);
        } catch (InvalidArgumentException $exception) {
            Notification::make()
                ->title(__('Could not save'))
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title(__('Food choices saved'))
            ->body(__('Canteen POS will show the updated names, prices, and quantities.'))
            ->success()
            ->send();
    }
}
