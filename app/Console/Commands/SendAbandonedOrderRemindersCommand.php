<?php

namespace App\Console\Commands;

use App\Services\OrderService;
use Illuminate\Console\Command;

class SendAbandonedOrderRemindersCommand extends Command
{
    protected $signature = 'snack:abandoned-reminders';

    protected $description = 'Queue SMS reminders for orders still waiting to be paid or confirmed.';

    public function handle(OrderService $orders): int
    {
        $count = $orders->sendAbandonedReminders();

        $this->info("Queued abandoned-order reminders for {$count} order(s).");

        return self::SUCCESS;
    }
}
