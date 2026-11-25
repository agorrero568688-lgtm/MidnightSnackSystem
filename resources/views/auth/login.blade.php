<x-layouts.guest>
    <x-slot:title>Sign in</x-slot:title>

    <div class="rounded-2xl border border-white/10 bg-ink-900/80 p-6 shadow-2xl backdrop-blur">
        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="username" class="label text-ink-400!">Username</label>
                <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username"
                    required autofocus
                    class="input border-white/10! bg-ink-950/70! text-white! placeholder:text-ink-600! focus:border-violet-400! focus:ring-violet-400/25!">
            </div>

            <div>
                <label for="password" class="label text-ink-400!">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                    class="input border-white/10! bg-ink-950/70! text-white! focus:border-violet-400! focus:ring-violet-400/25!">
            </div>

            <label class="flex items-center gap-2 text-sm text-ink-400">
                <input type="checkbox" name="remember" class="h-4 w-4 rounded border-white/20 bg-ink-950 text-brand-500">
                Keep me signed in
            </label>

            <button type="submit" class="btn btn-primary w-full">Sign in</button>
        </form>
    </div>

    @if (app()->environment('local'))
        <p class="mt-4 text-center text-xs text-ink-500">
            Seeded accounts — admin / <span class="font-mono">admin123</span> · cashier / <span class="font-mono">cashier123</span>
        </p>
    @endif
</x-layouts.guest>
