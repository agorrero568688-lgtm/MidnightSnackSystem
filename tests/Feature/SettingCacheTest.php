<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_loads_every_setting_in_one_query(): void
    {
        Setting::setMany([
            'store_name' => 'Test Store',
            'store_phone' => '0917 000 0000',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->assertSame('Test Store', Setting::get('store_name'));
        $this->assertSame('0917 000 0000', Setting::get('store_phone'));
        $this->assertSame('fallback', Setting::get('missing_setting', 'fallback'));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(1, $queries);
    }

    public function test_set_many_writes_every_setting_in_one_query(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        Setting::setMany([
            'store_name' => 'Test Store',
            'store_phone' => '0917 000 0000',
            'gcash_name' => 'Test Account',
        ]);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(1, $queries);
        $this->assertStringContainsString('insert into "settings"', strtolower($queries[0]['query']));

        $this->assertSame('Test Store', Setting::get('store_name'));
        $this->assertSame('0917 000 0000', Setting::get('store_phone'));
        $this->assertSame('Test Account', Setting::get('gcash_name'));
    }

    public function test_set_updates_the_cached_map(): void
    {
        Setting::set('store_name', 'First');
        $this->assertSame('First', Setting::get('store_name'));

        Setting::set('store_name', 'Second');
        $this->assertSame('Second', Setting::get('store_name'));
    }
}
