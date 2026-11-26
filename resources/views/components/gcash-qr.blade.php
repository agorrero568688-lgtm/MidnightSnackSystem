@props([
    'src' => null,
    'alt' => 'GCash QR code',
    'caption' => 'Tap to enlarge',
    'empty' => 'GCash QR not set',
])

@if ($src)
    <div x-data="{ open: false, failed: false }" @keydown.escape.window="open = false"
        {{ $attributes->merge(['class' => 'inline-flex shrink-0 flex-col items-center']) }}>
        <template x-if="! failed">
            <button type="button" @click="open = true"
                class="group flex cursor-zoom-in flex-col items-center gap-1 rounded-xl bg-white p-2.5 shadow-sm ring-1 ring-violet-200 transition hover:ring-violet-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500"
                aria-label="Enlarge GCash QR code">
                <img src="{{ $src }}" alt="{{ $alt }}" x-on:error="failed = true"
                    class="h-40 w-40 rounded-md object-contain sm:h-44 sm:w-44">
                <span class="text-[10px] font-semibold text-violet-700 group-hover:text-violet-900">{{ $caption }}</span>
            </button>
        </template>

        <div x-show="failed" x-cloak
            class="flex h-40 w-40 flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-violet-300 bg-white/60 p-3 text-center">
            <x-icon name="alert" class="h-5 w-5 text-violet-400" />
            <span class="text-[10px] font-semibold text-violet-700">QR code unavailable</span>
        </div>

        <div x-show="open && ! failed" x-cloak x-transition.opacity @click.self="open = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-ink-950/80 p-4 backdrop-blur-sm"
            role="dialog" aria-modal="true" aria-label="GCash QR code">
            <div class="w-full max-w-sm rounded-2xl bg-white p-3 shadow-2xl">
                <div class="flex items-center justify-between gap-3 px-1 pb-2">
                    <p class="text-sm font-bold text-ink-900">Scan to pay via GCash</p>
                    <button type="button" @click="open = false"
                        class="rounded-lg p-1.5 text-ink-500 transition hover:bg-ink-100 hover:text-ink-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500"
                        aria-label="Close QR code">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>
                <img src="{{ $src }}" alt="{{ $alt }}" x-on:error="failed = true"
                    class="mx-auto max-h-[70vh] w-full rounded-xl bg-white object-contain">
                <p class="px-1 pt-2 text-center text-[11px] text-ink-500">Tap outside or press Esc to close.</p>
            </div>
        </div>
    </div>
@else
    <div
        {{ $attributes->merge(['class' => 'flex h-40 w-40 flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-violet-300 bg-white/60 p-3 text-center']) }}>
        <x-icon name="alert" class="h-5 w-5 text-violet-400" />
        <span class="text-[10px] font-semibold text-violet-700">{{ $empty }}</span>
    </div>
@endif
