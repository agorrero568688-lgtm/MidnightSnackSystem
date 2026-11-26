<?php

namespace Tests\Unit;

use Tests\TestCase;

class BlobTokenConfigTest extends TestCase
{
    public function test_custom_blob_tokens_take_precedence_over_the_generated_tokens(): void
    {
        $config = $this->withBlobEnv([
            'BLOB_PUBLIC_TOKEN' => 'public-primary',
            'BLOB_PUBLIC_READ_WRITE_TOKEN' => 'public-generated',
            'BLOB_PRIVATE_TOKEN' => 'private-primary',
            'BLOB_PRIVATE_READ_WRITE_TOKEN' => 'private-generated',
        ], fn () => require base_path('config/services.php'));

        $this->assertSame('public-primary', $config['blob']['media']['token']);
        $this->assertSame('private-primary', $config['blob']['proofs']['token']);
    }

    public function test_blob_tokens_fall_back_to_the_generated_read_write_tokens(): void
    {
        $config = $this->withBlobEnv([
            'BLOB_PUBLIC_TOKEN' => '',
            'BLOB_PUBLIC_READ_WRITE_TOKEN' => 'public-generated',
            'BLOB_PRIVATE_TOKEN' => '',
            'BLOB_PRIVATE_READ_WRITE_TOKEN' => 'private-generated',
        ], fn () => require base_path('config/services.php'));

        $this->assertSame('public-generated', $config['blob']['media']['token']);
        $this->assertSame('private-generated', $config['blob']['proofs']['token']);
    }

    /**
     * @param  array<string, string>  $values
     */
    private function withBlobEnv(array $values, callable $callback): mixed
    {
        $keys = array_keys($values);
        $original = [];

        foreach ($keys as $key) {
            $original[$key] = [
                array_key_exists($key, $_ENV),
                $_ENV[$key] ?? null,
                array_key_exists($key, $_SERVER),
                $_SERVER[$key] ?? null,
            ];
        }

        try {
            foreach ($values as $key => $value) {
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }

            return $callback();
        } finally {
            foreach ($original as $key => [$inEnv, $envValue, $inServer, $serverValue]) {
                if ($inEnv) {
                    $_ENV[$key] = $envValue;
                } else {
                    unset($_ENV[$key]);
                }

                if ($inServer) {
                    $_SERVER[$key] = $serverValue;
                } else {
                    unset($_SERVER[$key]);
                }
            }
        }
    }
}
