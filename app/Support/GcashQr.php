<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Resolves the store's GCash QR image on the right disk.
 *
 * Vercel's local filesystem is ephemeral, so production uploads live in the
 * public Vercel Blob store (the same place product photos use). Local
 * development and tests keep using the "public" disk.
 */
class GcashQr
{
    public const DIRECTORY = 'store-settings';

    public static function diskName(): string
    {
        return 'public';
    }

    public static function disk(): Filesystem
    {
        return Storage::disk(static::diskName());
    }

    /** The configured QR path, normalised for disk operations. */
    public static function path(): ?string
    {
        return static::pathFor(Setting::get('gcash_qr_path'));
    }

    /** A browser URL for the configured QR, or null when it cannot be resolved. */
    public static function url(): ?string
    {
        return static::urlFor(Setting::get('gcash_qr_path'));
    }

    /**
     * Resolve a stored value (disk path or absolute URL) to a browser URL.
     */
    public static function urlFor(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        // Settings may store a full public/media URL or a data URI.
        if (PublicMediaUrl::isAbsolute($value)) {
            return $value;
        }

        $path = static::pathFor($value);

        if ($path === null) {
            return null;
        }

        // Remote media never gets an exists()/lastModified() call. Blob URLs
        // are stored absolutely; legacy relative paths use the old media URL.
        if (PublicMediaUrl::isRemote(static::diskName())) {
            return BlobStorage::url($value, BlobStorage::MEDIA, static::diskName());
        }

        try {
            if (! static::disk()->exists($path)) {
                return null;
            }

            return route('gcash.qr', ['v' => static::disk()->lastModified($path)], false);
        } catch (Throwable) {
            // A missing/unreachable QR asset must never crash the storefront.
            return null;
        }
    }

    /** Store an uploaded QR image on the active media storage. */
    public static function store(UploadedFile $file): string
    {
        return BlobStorage::store($file, self::DIRECTORY, BlobStorage::MEDIA, static::diskName());
    }

    /** Best-effort removal of a previously stored QR image. */
    public static function delete(?string $value): void
    {
        BlobStorage::delete($value, BlobStorage::MEDIA, static::diskName());
    }

    /** Normalise a stored value into a disk path, or null when it is not one. */
    public static function pathFor(?string $value): ?string
    {
        $path = trim((string) $value);

        if ($path === '' || static::isAbsolute($path)) {
            return null;
        }

        // Legacy values such as "storage/store-settings/qr.png".
        $path = ltrim((string) preg_replace('#^/?storage/#', '', $path), '/');

        return $path !== '' ? $path : null;
    }

    public static function isAbsolute(string $value): bool
    {
        return preg_match('#^(?:https?:)?//#i', $value) === 1;
    }
}
