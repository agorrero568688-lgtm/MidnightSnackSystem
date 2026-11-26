<div class="space-y-5">
    <div class="card card-body flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold">Confirm your password</p>
            <p class="text-xs text-ink-500">Required to create users, change roles or passwords, and deactivate accounts.</p>
            @error('confirmPassword') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <input wire:model="confirmPassword" type="password" class="input sm:w-72" autocomplete="current-password"
            placeholder="Your account password">
    </div>

    <div class="flex justify-end">
        <button wire:click="startCreate" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> New user
        </button>
    </div>

    @if ($showForm)
        <form wire:submit="save" class="card">
            <div class="card-header">
                <h2 class="text-sm font-bold">{{ $editingId ? 'Edit user' : 'New user' }}</h2>
                <button type="button" wire:click="cancel" class="btn btn-sm btn-ghost">Close</button>
            </div>
            <div class="card-body grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="label">Full name</label>
                    <input wire:model="name" type="text" class="input" placeholder="Juan Dela Cruz">
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Username</label>
                    <input wire:model="username" type="text" class="input font-mono" placeholder="juan">
                    @error('username') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Role</label>
                    <select wire:model="role" class="input">
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">{{ $editingId ? 'New password (optional)' : 'Password' }}</label>
                    <input wire:model="password" type="password" class="input" placeholder="At least 8 characters" autocomplete="new-password">
                    @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <label class="flex items-center gap-2 sm:col-span-2">
                    <input wire:model="isActive" type="checkbox" class="h-4 w-4 rounded border-ink-300 text-brand-500">
                    <span class="text-sm">Account is active</span>
                </label>
            </div>
            <div class="flex justify-end gap-2 border-t border-ink-100 px-5 py-3">
                <button type="button" wire:click="cancel" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">{{ $editingId ? 'Save changes' : 'Create user' }}</button>
            </div>
        </form>
    @endif

    <div class="card">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th class="text-right">Sales</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-ink-100 text-sm font-bold text-ink-600">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold">{{ $user->name }}</p>
                                        @if ($user->id === auth()->id())
                                            <p class="text-[11px] text-brand-700">That's you</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="font-mono text-xs">{{ $user->username }}</td>
                            <td>
                                <span class="badge {{ $user->isAdmin() ? 'badge-violet' : 'badge-sky' }}">{{ $user->role->label() }}</span>
                            </td>
                            <td class="text-right text-ink-600">{{ $user->sales_count }}</td>
                            <td>
                                @if ($user->is_active)
                                    <span class="badge badge-green">Active</span>
                                @else
                                    <span class="badge badge-slate">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    <button wire:click="startEdit({{ $user->id }})" class="btn btn-sm btn-ghost">Edit</button>
                                    <button wire:click="toggleActive({{ $user->id }})"
                                        class="btn btn-sm {{ $user->is_active ? 'text-rose-600 hover:bg-rose-50' : 'text-emerald-600 hover:bg-emerald-50' }}">
                                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2 class="text-sm font-bold">Recent sign-ins</h2></div>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Result</th>
                        <th>IP address</th>
                        <th>Device</th>
                        <th class="text-right">When</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($loginLogs as $log)
                        <tr wire:key="login-{{ $log->id }}">
                            <td class="font-mono text-xs">{{ $log->user?->name ?? $log->username }}</td>
                            <td>
                                <span class="badge {{ $log->isFailed() ? 'badge-red' : 'badge-green' }}">
                                    {{ $log->isFailed() ? 'Failed' : 'Signed in' }}
                                </span>
                            </td>
                            <td class="font-mono text-xs">{{ $log->ip_address ?? '—' }}</td>
                            <td class="max-w-64 truncate text-xs text-ink-600" title="{{ $log->user_agent }}">{{ $log->user_agent ?? '—' }}</td>
                            <td class="text-right text-xs text-ink-600">{{ $log->created_at?->format('M j, Y g:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-xs text-ink-500">No sign-ins recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-ink-500">
        Users are never deleted — deactivating keeps their sales history intact. Admins can manage products,
        orders and reports; cashiers only see the cashier screen and their own receipts.
    </p>
</div>
