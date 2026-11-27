<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_the_store_timezone(): void
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));
        $this->assertSame('Asia/Manila', now()->getTimezone()->getName());
    }

    public function test_it_shifts_existing_timestamps_to_store_time(): void
    {
        DB::table('settings')->insert([
            'key' => 'timezone-check',
            'value' => '1',
            'created_at' => '2026-11-27 04:00:00',
            'updated_at' => '2026-11-27 04:00:00',
        ]);

        $migration = require database_path(
            'migrations/2026_11_27_000001_shift_stored_timestamps_to_manila.php'
        );
        $migration->up();

        $this->assertSame(
            '2026-11-27 12:00:00',
            DB::table('settings')->where('key', 'timezone-check')->value('created_at')
        );
    }
}
