<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * The till keeps a short-lived copy of the catalogue so a page refresh does not
 * hammer a remote database. Flush it whenever products or stock change.
 */
final class PosCatalog
{
    public const KEY = 'pos.catalog';

    public static function flush(): void
    {
        Cache::forget(self::KEY);
    }
}
