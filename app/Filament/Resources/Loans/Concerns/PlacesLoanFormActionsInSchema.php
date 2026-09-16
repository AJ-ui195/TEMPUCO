<?php

namespace App\Filament\Resources\Loans\Concerns;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;

trait PlacesLoanFormActionsInSchema
{
    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler($this->getSubmitFormLivewireMethodName());
    }
}
