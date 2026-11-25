/**
 * Menu photo upload helper.
 *
 * Laravel accepts up to 10 MB, but hosting platforms such as Vercel cap the
 * whole request body below that. Large images are resized in the browser
 * before being handed to Livewire so the upload stays small and fast.
 */
window.menuPhotoUpload = function () {
    const MAX_BYTES = 10 * 1024 * 1024;
    const SAFE_BYTES = 3.5 * 1024 * 1024;
    const PLATFORM_BYTES = 4 * 1024 * 1024;
    const TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    return {
        preview: null,
        preparing: false,
        uploading: false,
        progress: 0,
        error: '',

        selectPhoto(event) {
            const input = event.target;
            const file = input.files?.[0];

            this.error = '';
            this.progress = 0;

            if (! file) {
                return;
            }

            if (! TYPES.includes(file.type)) {
                this.fail(input, 'Choose a JPG, PNG or WebP photo.');
                return;
            }

            if (file.size > MAX_BYTES) {
                this.fail(input, 'That photo is larger than 10 MB. Choose a smaller one.');
                return;
            }

            this.setPreview(file);
            this.preparing = true;

            this.prepare(file)
                .then((upload) => {
                    this.preparing = false;

                    if (upload.size > PLATFORM_BYTES) {
                        this.fail(input, 'That photo could not be made small enough to upload. Try another photo.');
                        return;
                    }

                    this.upload(upload, input);
                })
                .catch(() => {
                    this.preparing = false;
                    this.fail(input, 'That photo could not be prepared. Try a JPG, PNG or WebP file.');
                });
        },

        async prepare(file) {
            // Small files already fit comfortably under the host request limit.
            if (file.size <= SAFE_BYTES) {
                return file;
            }

            const bitmap = await createImageBitmap(file);
            const attempts = [
                { size: 1920, quality: 0.85 },
                { size: 1600, quality: 0.8 },
                { size: 1280, quality: 0.75 },
                { size: 1024, quality: 0.7 },
            ];

            let upload = file;

            try {
                for (const attempt of attempts) {
                    const blob = await this.render(bitmap, attempt.size, attempt.quality);

                    if (! blob) {
                        continue;
                    }

                    upload = new File([blob], this.safeName(file.name), {
                        type: 'image/jpeg',
                        lastModified: Date.now(),
                    });

                    if (upload.size <= SAFE_BYTES) {
                        break;
                    }
                }
            } finally {
                bitmap.close?.();
            }

            return upload;
        },

        render(bitmap, maxSize, quality) {
            const scale = Math.min(1, maxSize / Math.max(bitmap.width, bitmap.height));
            const width = Math.max(1, Math.round(bitmap.width * scale));
            const height = Math.max(1, Math.round(bitmap.height * scale));
            const canvas = document.createElement('canvas');

            canvas.width = width;
            canvas.height = height;

            const context = canvas.getContext('2d');

            if (! context) {
                return Promise.resolve(null);
            }

            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, width, height);
            context.imageSmoothingEnabled = true;
            context.imageSmoothingQuality = 'high';
            context.drawImage(bitmap, 0, 0, width, height);

            return new Promise((resolve) => {
                canvas.toBlob(resolve, 'image/jpeg', quality);
            });
        },

        upload(file, input) {
            this.uploading = true;

            this.$wire.upload(
                'image',
                file,
                () => {
                    this.uploading = false;
                },
                () => {
                    this.uploading = false;
                    this.fail(input, 'Upload failed. Check your connection and try again.');
                },
                (event) => {
                    this.progress = event.total
                        ? Math.round((event.loaded / event.total) * 100)
                        : 0;
                },
            );
        },

        setPreview(file) {
            this.clearPreview();
            this.preview = URL.createObjectURL(file);
        },

        clearPreview() {
            if (this.preview) {
                URL.revokeObjectURL(this.preview);
                this.preview = null;
            }
        },

        fail(input, message) {
            this.error = message;
            input.value = '';
            this.clearPreview();
        },

        safeName(name) {
            const base = name.replace(/\.[^.]+$/, '') || 'menu-photo';

            return `${base}.jpg`;
        },
    };
};
