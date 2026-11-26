<?php

namespace Tests;

use App\Models\Setting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Settings are memoized per process; reset them between tests.
        Setting::flushCache();
    }

    /**
     * A tiny valid PNG. UploadedFile::fake()->image() needs the GD extension,
     * which is not always installed, so tests build the file from bytes.
     */
    protected function fakeImageUpload(string $name = 'receipt.png'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'proof');

        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        ));

        return new UploadedFile($path, $name, 'image/png', null, true);
    }
}
