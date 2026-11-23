<x-layouts.guest>
    <x-slot:title>Sign in</x-slot:title>

    <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-2xl">
        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="username" class="label text-slate-400!">Username</label>
                <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username"
                    required autofocus
                    class="input border-slate-700! bg-slate-950! text-white! placeholder:text-slate-600! focus:border-amber-400! focus:ring-amber-400/30!">
            </div>

            <div>
                <label for="password" class="label text-slate-400!">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                    class="input border-slate-700! bg-slate-950! text-white! focus:border-amber-400! focus:ring-amber-400/30!">
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-400">
                <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-amber-500">
                Keep me signed in
            </label>

            <button type="submit" class="btn btn-primary w-full">Sign in</button>
        </form>
    </div>

    @if (app()->environment('local'))
        <p class="mt-4 text-center text-xs text-slate-500">
            Seeded accounts — admin / <span class="font-mono">admin123</span> · cashier / <span class="font-mono">cashier123</span>
        </p>
    @endif
</x-layouts.guest>
