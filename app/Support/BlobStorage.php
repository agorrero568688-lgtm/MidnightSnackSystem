<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Storage gateway for the two things that must survive a Vercel deployment:
 * public menu/QR images and private GCash receipts.
 *
 * Local development keeps using Laravel disks. Production sets MEDIA_DRIVER or
 * PROOF_DRIVER to "blob", where files are written directly to Vercel Blob over
 * its REST API. New rows always store the absolute URL returned by Blob, so
 * rendering never needs another storage request.
 */
class BlobStorage
{
    public const MEDIA = 'media';

    public const PROOFS = 'proofs';

    public const API_VERSION = '13';

    public static function usesBlob(string $kind): bool
    {
        return static::driver($kind) === 'blob';
    }

    private static function driver(string $kind): string
    {
        return (string) (config("services.blob.{$kind}.driver") ?: 'local');
    }

    /** Store an upload, returning either a disk path or an absolute Blob URL. */
    public static function store(UploadedFile $file, string $directory, string $kind, ?string $diskName = null): string
    {
        if (! static::usesBlob($kind)) {
            return VerifiedUpload::store($file, $directory, static::diskName($kind, $diskName));
        }

        return static::put($file, $directory, $kind);
    }

    /** Resolve a stored value into a browser URL (public media only). */
    public static function url(?string $value, string $kind = self::MEDIA, ?string $diskName = null): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (static::isDataUri($value)) {
            return null;
        }

        if (PublicMediaUrl::isAbsolute($value)) {
            return $value;
        }

        $path = PublicMediaUrl::path($value);

        if ($path === null) {
            return null;
        }

        if (static::usesBlob($kind)) {
            return static::urlForPath($path, $kind);
        }

        return PublicMediaUrl::resolve($value, static::diskName($kind, $diskName));
    }

    /**
     * Open a receipt for streaming through OrderProofController.
     *
     * @return array{stream: resource, content_type: ?string}
     */
    public static function read(?string $value, string $kind = self::PROOFS, ?string $diskName = null): array
    {
        $value = trim((string) $value);

        if ($value === '' || static::isDataUri($value)) {
            throw new RuntimeException('Stored receipt is empty.');
        }

        if (! static::usesBlob($kind)) {
            $stream = Storage::disk(static::diskName($kind, $diskName))->readStream(PublicMediaUrl::path($value) ?: $value);

            if (! is_resource($stream)) {
                throw new RuntimeException('Could not open the stored receipt.');
            }

            return ['stream' => $stream, 'content_type' => null];
        }

        $url = PublicMediaUrl::isAbsolute($value)
            ? $value
            : static::legacyProofUrl(PublicMediaUrl::path($value) ?: $value);

        if ($url === null) {
            throw new RuntimeException('Stored receipt cannot be resolved.');
        }

        $headers = static::isBlobUrl($url, $kind)
            ? ['Authorization' => 'Bearer '.static::token($kind), 'Accept' => '*/*']
            : static::legacyProofHeaders();

        $response = Http::withHeaders($headers)
            ->timeout(15)
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('The stored receipt could not be read.');
        }

        $stream = fopen('php://temp', 'w+b');

        if (! is_resource($stream)) {
            throw new RuntimeException('Could not create a receipt stream.');
        }

        fwrite($stream, $response->body());
        rewind($stream);

        return [
            'stream' => $stream,
            'content_type' => $response->header('Content-Type'),
        ];
    }

    /** Best-effort cleanup, called only after the database points elsewhere. */
    public static function delete(?string $value, string $kind, ?string $diskName = null): void
    {
        $value = trim((string) $value);

        if ($value === '' || static::isDataUri($value)) {
            return;
        }

        if (! static::usesBlob($kind)) {
            $path = PublicMediaUrl::path($value);

            if ($path !== null) {
                try {
                    Storage::disk(static::diskName($kind, $diskName))->delete($path);
                } catch (Throwable) {
                    // Cleanup must never mask a successful save.
                }
            }

            return;
        }

        $url = PublicMediaUrl::isAbsolute($value)
            ? $value
            : static::urlForPath(PublicMediaUrl::path($value) ?: '', $kind);

        // Never send a delete request for a legacy Supabase URL or any other
        // host we do not own.
        if ($url === '' || ! static::isBlobUrl($url, $kind)) {
            return;
        }

        try {
            $response = Http::withHeaders(static::controlHeaders($kind))
                ->timeout(15)
                ->post(rtrim(static::apiUrl(), '/').'/delete', [
                    'urls' => [$url],
                ]);

            if (! $response->successful()) {
                Log::warning('Blob delete failed', [
                    'kind' => $kind,
                    'status' => $response->status(),
                ]);
            }
        } catch (Throwable $exception) {
            Log::warning('Blob delete failed', [
                'kind' => $kind,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /** A filename usable for Content-Disposition, even for absolute URLs. */
    public static function filename(?string $value): string
    {
        $value = trim((string) $value);
        $path = parse_url($value, PHP_URL_PATH) ?: $value;

        return basename($path) ?: 'receipt';
    }

    private static function put(UploadedFile $file, string $directory, string $kind): string
    {
        $token = static::token($kind);
        $pathname = trim($directory, '/').'/'.$file->hashName();
        $mime = $file->getMimeType() ?: 'application/octet-stream';

        try {
            $stream = method_exists($file, 'readStream')
                ? $file->readStream()
                : fopen((string) $file->getRealPath(), 'rb');

            if (! is_resource($stream)) {
                throw new RuntimeException('Could not open the uploaded file for reading.');
            }

            $contents = stream_get_contents($stream);
            fclose($stream);

            if (! is_string($contents)) {
                throw new RuntimeException('Could not read the uploaded file.');
            }

            $url = rtrim(static::apiUrl(), '/').'/?pathname='.rawurlencode($pathname);

            $response = Http::withHeaders(array_merge(static::controlHeaders($kind), [
                'Accept' => 'application/json',
                'Content-Type' => $mime,
                'x-vercel-blob-access' => $kind === self::MEDIA ? 'public' : 'private',
                'x-content-type' => $mime,
                'x-add-random-suffix' => '0',
                'x-allow-overwrite' => '0',
                'x-cache-control-max-age' => (string) static::cacheControl($kind),
            ]))
                ->timeout(20)
                ->withBody($contents, $mime)
                ->send('PUT', $url);

            if (! $response->successful()) {
                throw new RuntimeException('Vercel Blob rejected the upload ('.$response->status().').');
            }

            $uploadedUrl = $response->json('url');

            if (! is_string($uploadedUrl) || ! str_starts_with($uploadedUrl, 'https://')) {
                throw new RuntimeException('Vercel Blob did not confirm the upload URL.');
            }

            return $uploadedUrl;
        } catch (Throwable $exception) {
            Log::warning('Blob upload failed', [
                'kind' => $kind,
                'path' => $pathname,
                'error' => $exception->getMessage(),
            ]);

            throw new RuntimeException(
                'Could not save the uploaded file. Please try again.',
                0,
                $exception,
            );
        }
    }

    /** @return array<string, string> */
    private static function controlHeaders(string $kind): array
    {
        return [
            'Authorization' => 'Bearer '.static::token($kind),
            'x-api-version' => self::API_VERSION,
            'x-vercel-blob-store-id' => static::storeId($kind),
            'x-api-blob-request-id' => bin2hex(random_bytes(12)),
            'x-api-blob-request-attempt' => '0',
        ];
    }

    private static function token(string $kind): string
    {
        $token = trim((string) config("services.blob.{$kind}.token"));

        if ($token === '') {
            throw new RuntimeException('Vercel Blob token is not configured for '.$kind.'.');
        }

        return $token;
    }

    private static function storeId(string $kind): string
    {
        $configured = trim((string) config("services.blob.{$kind}.store_id"));

        if ($configured !== '') {
            return preg_replace('/^store_/', '', $configured) ?: $configured;
        }

        // Read-write tokens look like vercel_blob_rw_<storeId>_<secret>.
        $parts = explode('_', static::token($kind));
        $storeId = $parts[3] ?? '';

        if ($storeId === '') {
            throw new RuntimeException('Vercel Blob store ID could not be resolved for '.$kind.'.');
        }

        return $storeId;
    }

    private static function apiUrl(): string
    {
        return rtrim((string) (config('services.blob.api_url') ?: 'https://vercel.com/api/blob'), '/');
    }

    private static function cacheControl(string $kind): int
    {
        return (int) config(
            'services.blob.'.$kind.'.cache_control',
            $kind === self::MEDIA ? 86400 : 60,
        );
    }

    private static function baseUrl(string $kind): ?string
    {
        $configured = trim((string) config("services.blob.{$kind}.base_url"));

        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        try {
            $access = $kind === self::MEDIA ? 'public' : 'private';

            return 'https://'.static::storeId($kind).'.'.$access.'.blob.vercel-storage.com';
        } catch (Throwable) {
            return null;
        }
    }

    private static function urlForPath(string $path, string $kind): ?string
    {
        $path = ltrim($path, '/');

        if ($path === '') {
            return null;
        }

        if ($kind === self::MEDIA) {
            $legacy = trim((string) config('services.blob.legacy_media_url'));

            if ($legacy !== '') {
                return rtrim($legacy, '/').'/'.$path;
            }
        }

        $base = static::baseUrl($kind);

        return $base === null ? null : $base.'/'.$path;
    }

    private static function isBlobUrl(string $url, string $kind): bool
    {
        $base = static::baseUrl($kind);

        if ($base === null) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $baseHost = parse_url($base, PHP_URL_HOST);

        return is_string($host) && is_string($baseHost)
            && strcasecmp($host, $baseHost) === 0;
    }

    private static function legacyProofUrl(string $path): ?string
    {
        $base = trim((string) config('services.blob.legacy_proofs.url'));
        $key = trim((string) config('services.blob.legacy_proofs.key'));

        if ($base === '' || $key === '') {
            return null;
        }

        $bucket = rawurlencode((string) config('services.blob.legacy_proofs.bucket', 'proofs'));
        $object = implode('/', array_map('rawurlencode', explode('/', ltrim($path, '/'))));

        return rtrim($base, '/').'/storage/v1/object/'.$bucket.'/'.$object;
    }

    /** @return array<string, string> */
    private static function legacyProofHeaders(): array
    {
        $key = trim((string) config('services.blob.legacy_proofs.key'));

        return [
            'Authorization' => 'Bearer '.$key,
            'apikey' => $key,
            'Accept' => '*/*',
        ];
    }

    private static function isDataUri(string $value): bool
    {
        return str_starts_with($value, 'data:');
    }

    private static function diskName(string $kind, ?string $diskName): string
    {
        return $diskName ?: ($kind === self::MEDIA ? 'public' : 'local');
    }
}
