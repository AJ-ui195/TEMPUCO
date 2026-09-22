<?php

namespace App\Filament\CollectionCashier\Concerns;

use App\Support\MemberEditPin;

trait RequiresMemberEditPin
{
    public bool $showPinModal = false;

    public string $pendingPinAction = '';

    public string $actionPin = '';

    public ?string $pinError = null;

    /**
     * @var list<string>
     */
    protected array $pinProtectedActions = [
        'cancelOfficialReceipt',
        'deletePaymentDraft',
        'updatePayment',
    ];

    protected bool $pinGatePassed = false;

    public function requestPinAction(string $action): void
    {
        if (! $this->isPinProtectedAction($action)) {
            return;
        }

        $this->pendingPinAction = $action;
        $this->actionPin = '';
        $this->pinError = null;
        $this->showPinModal = true;
    }

    public function closePinModal(): void
    {
        $this->showPinModal = false;
        $this->pendingPinAction = '';
        $this->actionPin = '';
        $this->pinError = null;
    }

    public function confirmPinAction(): void
    {
        if (! MemberEditPin::check($this->actionPin)) {
            $this->pinError = __('The PIN is incorrect.');

            return;
        }

        $action = $this->pendingPinAction;
        $this->closePinModal();

        if (! $this->isPinProtectedAction($action) || ! method_exists($this, $action)) {
            return;
        }

        $this->pinGatePassed = true;
        $this->{$action}();
    }

    protected function consumePinGate(string $action): bool
    {
        if ($this->pinGatePassed) {
            $this->pinGatePassed = false;

            return true;
        }

        $this->requestPinAction($action);

        return false;
    }

    protected function isPinProtectedAction(string $action): bool
    {
        return in_array($action, $this->pinProtectedActions, true);
    }

    public function pinActionLabel(): string
    {
        return match ($this->pendingPinAction) {
            'cancelOfficialReceipt' => __('Cancelled OR#'),
            'deletePaymentDraft' => __('Delete payment'),
            'updatePayment' => __('Update payment'),
            default => __('Confirm'),
        };
    }
}
