<?php

namespace Tests\Unit;

use Tests\TestCase;

class FilesystemConfigTest extends TestCase
{
    public function test_s3_disks_use_short_http_timeouts_and_a_single_retry(): void
    {
        $config = require base_path('config/filesystems.php');

        foreach (['local', 'public', 's3'] as $name) {
            $disk = $config['disks'][$name];

            if (($disk['driver'] ?? null) !== 's3') {
                continue;
            }

            $this->assertSame(3, $disk['http']['connect_timeout'], "{$name} connect timeout");
            $this->assertSame(5, $disk['http']['timeout'], "{$name} timeout");
            $this->assertSame(1, $disk['retries'], "{$name} retries");
        }

        $this->assertSame(3, $config['disks']['s3']['http']['connect_timeout']);
        $this->assertSame(5, $config['disks']['s3']['http']['timeout']);
        $this->assertSame(1, $config['disks']['s3']['retries']);
    }
}
