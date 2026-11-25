<x-layouts.storefront :show-cart="false" title="Rate your snack">
    <main class="mx-auto max-w-xl px-4 py-10 sm:px-6">
        <div class="rounded-3xl border border-ink-200 bg-white p-6 shadow-sm">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-100 text-brand-700">
                <x-icon name="receipt" class="h-6 w-6" />
            </div>

            <h1 class="mt-4 text-2xl font-black tracking-tight">How was your snack?</h1>
            <p class="mt-1 text-sm text-ink-500">Help us keep the kitchen sharp — tap a rating below.</p>

            @if (session('status'))
                <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    {{ session('status') }}
                </div>
            @elseif ($alreadyFeedback)
                <div class="mt-5 rounded-xl border border-ink-200 bg-ink-50 px-4 py-3 text-sm text-ink-500">
                    Thanks — you already left feedback for this order.
                </div>
            @else
                <form method="POST" action="{{ route('feedback.update', $token) }}" class="mt-5 space-y-4">
                    @csrf
                    <div class="flex gap-2">
                        @foreach ([5 => 'Excellent 😋', 4 => 'Good 🙂', 3 => 'OK 😐', 2 => 'Poor 🙁', 1 => 'Bad 😕'] as $value => $label)
                            <button type="submit" name="rating" value="{{ $value }}"
                                class="btn {{ $value === 5 ? 'btn-primary' : 'btn-secondary' }} flex-1 py-4 text-sm font-bold">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </form>
            @endif

            <p class="mt-5 text-xs text-ink-400">
                Your feedback is stored privately by the store and not shared outside.
            </p>
        </div>
    </main>
</x-layouts.storefront>
