<?php

namespace App\Console\Commands;

use App\Support\ExpirationNotifier;
use Illuminate\Console\Command;

class NotifyExpiringProductsCommand extends Command
{
    protected $signature = 'pos:notify-expiring-products';

    protected $description = 'Notify grocery cashiers about products expiring within 2 weeks';

    public function handle(): int
    {
        $count = ExpirationNotifier::notifyDaily();

        if ($count === 0) {
            $this->info(__('No products are within 2 weeks of expiration.'));

            return self::SUCCESS;
        }

        $this->info(trans_choice(
            'Notified cashiers about :count expiring product.|Notified cashiers about :count expiring products.',
            $count,
            ['count' => $count],
        ));

        return self::SUCCESS;
    }
}
