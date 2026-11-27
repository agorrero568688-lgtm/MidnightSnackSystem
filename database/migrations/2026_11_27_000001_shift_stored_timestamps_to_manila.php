<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wall-clock values were written while config/app.php forced UTC. Move the
     * existing values to store-local time now that APP_TIMEZONE is respected.
     * Asia/Manila has no DST, so a fixed offset is safe.
     *
     * @var array<string, list<string>>
     */
    private array $timestampColumns = [
        'users' => ['created_at', 'updated_at'],
        'categories' => ['created_at', 'updated_at'],
        'products' => ['created_at', 'updated_at'],
        'stock_movements' => ['created_at', 'updated_at'],
        'sales' => ['created_at', 'updated_at', 'voided_at'],
        'sale_items' => ['created_at', 'updated_at'],
        'shifts' => ['opened_at', 'closed_at', 'created_at', 'updated_at'],
        'orders' => [
            'placed_at',
            'confirmed_at',
            'ready_at',
            'delivered_at',
            'completed_at',
            'cancelled_at',
            'paid_at',
            'alert_sent_at',
            'created_at',
            'updated_at',
        ],
        'order_items' => ['created_at', 'updated_at'],
        'customer_feedback' => ['created_at', 'updated_at'],
        'settings' => ['created_at', 'updated_at'],
        'password_reset_tokens' => ['created_at'],
        'login_logs' => ['created_at'],
        'failed_jobs' => ['failed_at'],
    ];

    public function up(): void
    {
        $this->shiftTimestamps(8);
    }

    public function down(): void
    {
        // Best effort: this also moves rows written after the original fix.
        $this->shiftTimestamps(-8);
    }

    private function shiftTimestamps(int $hours): void
    {
        $driver = DB::connection()->getDriverName();

        DB::transaction(function () use ($driver, $hours): void {
            foreach ($this->timestampColumns as $table => $columns) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                foreach ($columns as $column) {
                    if (! Schema::hasColumn($table, $column)) {
                        continue;
                    }

                    DB::table($table)
                        ->whereNotNull($column)
                        ->update([
                            $column => DB::raw($this->expression($driver, $column, $hours)),
                        ]);
                }
            }
        });
    }

    private function expression(string $driver, string $column, int $hours): string
    {
        $interval = abs($hours).' hours';
        $sign = $hours < 0 ? '-' : '+';

        return match ($driver) {
            'pgsql' => "{$column} {$sign} interval '{$interval}'",
            'sqlite' => "datetime({$column}, '{$sign}{$interval}')",
            default => throw new \RuntimeException(
                "Unsupported database driver [{$driver}] for the timezone data migration."
            ),
        };
    }
};
