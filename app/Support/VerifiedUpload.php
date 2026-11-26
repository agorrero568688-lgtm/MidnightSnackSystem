<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Stores an uploaded file and guarantees the write actually happened.
 *
 * Livewire's TemporaryUploadedFile::storeAs() always returns a path, even when
 * the underlying Storage::put() returned false. Callers must never trust that
 * value, or they will persist a path for a file that was never written.
 */
class VerifiedUpload
{
    /**
     * @throws RuntimeException when the stream cannot be read or the write fails
     */
    public static function store(UploadedFile $file, string $directory, string $diskName): string
    {
        $path = trim($directory, '/').'/'.$file->hashName();
        $stream = null;

        try {
            // Livewire keeps temporary uploads on a disk, not at getRealPath().
            $stream = method_exists($file, 'readStream')
                ? $file->readStream()
                : fopen((string) $file->getRealPath(), 'rb');

            if (! is_resource($stream)) {
                throw new RuntimeException('Could not open the uploaded file for reading.');
            }

            if (Storage::disk($diskName)->put($path, $stream) !== true) {
                throw new RuntimeException('The storage disk did not confirm the write.');
            }

            return $path;
        } catch (Throwable $exception) {
            Log::warning('Upload failed', [
                'disk' => $diskName,
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);

            throw new RuntimeException(
                'Could not save the uploaded file. Please try again.',
                0,
                $exception,
            );
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
