<x-layouts.storefront :show-cart="false" title="Track an order">
    <main class="mx-auto max-w-lg px-4 py-12 sm:px-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                <x-icon name="receipt" class="h-6 w-6" />
            </div>

            <h1 class="mt-4 text-2xl font-black tracking-tight">Track your order</h1>
            <p class="mt-1 text-sm text-slate-500">Enter the reference from your confirmation and the phone number you used.</p>

            @if ($errors->any())
                <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('orders.lookup.submit') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="label">Order reference</label>
                    <input name="reference" type="text" value="{{ old('reference') }}" placeholder="ORD-000123"
                        class="input font-mono uppercase" autofocus>
                </div>
                <div>
                    <label class="label">Mobile number</label>
                    <input name="phone" type="tel" value="{{ old('phone') }}" placeholder="0917 123 4567" class="input">
                </div>
                <button type="submit" class="btn btn-primary w-full">Find my order</button>
            </form>

            <p class="mt-5 text-center text-xs text-slate-500">
                Lost your reference? Call the store{{ $config['store_phone'] ? ' at '.$config['store_phone'] : '' }}.
            </p>
        </div>

        <div class="mt-5 text-center">
            <a href="{{ route('storefront') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-slate-900">
                <x-icon name="arrow-left" class="h-4 w-4" /> Back to the menu
            </a>
        </div>
    </main>
</x-layouts.storefront>
