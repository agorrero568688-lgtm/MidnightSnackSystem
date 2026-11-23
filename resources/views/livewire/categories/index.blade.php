<div class="grid gap-5 lg:grid-cols-3">
    <div class="card h-fit">
        <div class="card-header"><h2 class="text-sm font-bold">Add category</h2></div>
        <form wire:submit="save" class="card-body space-y-3">
            <div>
                <label class="label">Name</label>
                <input wire:model="name" type="text" class="input" placeholder="e.g. Chips &amp; Crackers">
                @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn btn-primary w-full">
                <x-icon name="plus" class="h-4 w-4" /> Add category
            </button>
        </form>
    </div>

    <div class="card lg:col-span-2">
        <div class="card-header">
            <h2 class="text-sm font-bold">Categories</h2>
            <span class="badge badge-slate">{{ $categories->count() }}</span>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse ($categories as $category)
                <div wire:key="category-{{ $category->id }}" class="flex items-center gap-3 px-5 py-3.5">
                    @if ($editingId === $category->id)
                        <input wire:model="editingName" type="text" class="input input-sm flex-1"
                            wire:keydown.enter="update">
                        @error('editingName') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        <button wire:click="update" class="btn btn-sm btn-primary">Save</button>
                        <button wire:click="cancelEdit" class="btn btn-sm btn-ghost">Cancel</button>
                    @else
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                            <x-icon name="tag" class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold {{ $category->is_active ? '' : 'text-slate-400 line-through' }}">
                                {{ $category->name }}
                            </p>
                            <p class="text-xs text-slate-500">{{ $category->products_count }} product{{ $category->products_count === 1 ? '' : 's' }}</p>
                        </div>
                        @unless ($category->is_active)
                            <span class="badge badge-slate">Hidden</span>
                        @endunless
                        <button wire:click="startEdit({{ $category->id }})" class="btn btn-sm btn-ghost">Rename</button>
                        <button wire:click="toggle({{ $category->id }})" class="btn btn-sm btn-ghost">
                            {{ $category->is_active ? 'Hide' : 'Show' }}
                        </button>
                        <button wire:click="delete({{ $category->id }})"
                            wire:confirm="Delete {{ $category->name }}?"
                            class="btn btn-sm text-rose-600 hover:bg-rose-50">Delete</button>
                    @endif
                </div>
            @empty
                <p class="empty-state">No categories yet. Add one on the left.</p>
            @endforelse
        </div>
    </div>
</div>
