<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearTransactionsCommand extends Command
{
    protected $signature = 'db:clear-transactions
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Delete sales, online orders, feedback, and shifts while keeping users, products, and settings.';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm(
            'This permanently deletes all sales, online orders, feedback, and shifts. Continue?',
            false,
        )) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        $tables = [
            'customer_feedback',
            'order_items',
            'orders',
            'sale_items',
            'sales',
            'shifts',
            'stock_movements',
        ];

        $deleted = DB::transaction(function () use ($tables): array {
            $counts = [];

            foreach ($tables as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $counts[$table] = DB::table($table)->delete();
            }

            return $counts;
        });

        $this->info('Transactions cleared.');

        $this->table(
            ['Table', 'Rows deleted'],
            collect($deleted)
                ->map(fn (int $count, string $table): array => [$table, $count])
                ->values()
                ->all(),
        );

        return self::SUCCESS;
    }
}
