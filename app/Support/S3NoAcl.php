<?php

namespace App\Support;

use Aws\CommandInterface;

/**
 * Removes legacy ACL parameters before an S3 upload.
 *
 * Supabase Storage's S3 API does not support x-amz-acl (or the grant headers).
 * Laravel/Flysystem otherwise add one by default, which makes every write fail.
 * The bucket policy controls public access instead.
 */
class S3NoAcl
{
    /**
     * AWS SDK `before_upload` callback.
     */
    public static function stripAcl(CommandInterface $command): void
    {
        foreach (['ACL', 'GrantFullControl', 'GrantRead', 'GrantReadACP', 'GrantWrite', 'GrantWriteACP'] as $parameter) {
            unset($command[$parameter]);
        }
    }
}
