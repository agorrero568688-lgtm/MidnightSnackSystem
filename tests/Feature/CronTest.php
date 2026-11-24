<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CronTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_requests_without_the_cron_secret(): void
    {
        config(['services.cron.secret' => 'top-secret']);

        $this->get('/cron/abandoned-reminders')->assertForbidden();
    }

    public function test_it_rejects_requests_with_the_wrong_secret(): void
    {
        config(['services.cron.secret' => 'top-secret']);

        $this->withHeader('Authorization', 'Bearer wrong')
            ->get('/cron/abandoned-reminders')
            ->assertForbidden();
    }

    public function test_it_runs_the_reminder_command_with_the_secret(): void
    {
        config(['services.cron.secret' => 'top-secret']);
        Queue::fake();

        $this->withHeader('Authorization', 'Bearer top-secret')
            ->get('/cron/abandoned-reminders')
            ->assertOk()
            ->assertJson(['ok' => true]);
    }
}
