<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Resolves stored media paths into browser URLs without remote I/O.
 *
 * Rendering product photos or the GCash QR must never issue a HEAD request to
 * Supabase S3: a slow cross-region response makes the whole storefront time
 * out. Remote media is addressed directly from the configured public URL and
 * the browser's onerror fallback hides anything that is actually missing.
 */
class PublicMediaUrl
{
    /** Resolve a stored value (path or absolute URL) into a browser URL. */
    public static function resolve(?string $value, string $diskName = 'public'): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (static::isAbsolute($value)) {
            return $value;
        }

        $path = static::path($value);

        if ($path === null) {
            return null;
        }

        if (static::isBundledAsset($path)) {
            return asset($path);
        }

        if (static::isRemote($diskName)) {
            return static::remote($path, $diskName);
        }

        return static::local($path, $diskName);
    }

    /**
     * Build a remote URL directly from configuration.
     *
     * No exists()/lastModified() call is made here. The public disk URL is
     * AWS_MEDIA_URL in production; the disk's own URL is a test/fallback.
     */
    public static function remote(string $path, string $diskName = 'public'): ?string
    {
        // The browser URL must come from AWS_MEDIA_URL. Falling back to the
        // S3 endpoint would produce an address the browser cannot load.
        $base = config('filesystems.disks.public.url');

        if (blank($base)) {
            Log::error('Public media URL is not configured; set AWS_MEDIA_URL.', [
                'disk' => $diskName,
            ]);

            return null;
        }

        return rtrim((string) $base, '/').'/'.ltrim($path, '/');
    }

    /** True when either the requested disk or the public disk is on S3. */
    public static function isRemote(string $diskName = 'public'): bool
    {
        return config("filesystems.disks.{$diskName}.driver") === 's3'
            || config('filesystems.disks.public.driver') === 's3';
    }

    public static function isAbsolute(string $value): bool
    {
        return str_starts_with($value, 'data:')
            || preg_match('#^(?:https?:)?//#i', $value) === 1;
    }

    /** Normalise a stored value into a disk-relative path. */
    public static function path(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || static::isAbsolute($value)) {
            return null;
        }

        $path = ltrim((string) preg_replace('#^/?storage/#', '', $value), '/');

        // Older rows may include the public/ prefix for bundled menu art.
        if (str_starts_with($path, 'public/images/')) {
            $path = substr($path, strlen('public/'));
        }

        return $path !== '' ? $path : null;
    }

    /** Bundled assets ship in the image, so they never live on S3. */
    protected static function isBundledAsset(string $path): bool
    {
        return str_starts_with($path, 'images/') && is_file(public_path($path));
    }

    /** Local disks are cheap to inspect; keep hiding genuinely missing files. */
    protected static function local(string $path, string $diskName): ?string
    {
        try {
            if (! Storage::disk($diskName)->exists($path)) {
                return null;
            }

            return (string) Storage::disk($diskName)->url($path);
        } catch (Throwable) {
            return null;
        }
    }
}
