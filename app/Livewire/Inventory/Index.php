<?php

namespace App\Livewire\Inventory;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Services\InventoryService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Index extends Component
{
    public string $tab = 'levels';

    public string $search = '';

    public string $categoryFilter = 'all';

    public string $logType = 'all';

    public string $inventoryId = '';

    public string $mode = 'in';

    public string $qty = '';

    public string $note = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $category = 'Meat & Seafood';

    public string $quantity = '0';

    public string $unit = 'kg';

    public string $reorderLevel = '0';

    public string $costPerUnit = '0.00';

    public bool $isActive = true;

    public function updatedTab(): void
    {
        $this->resetErrorBag();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetErrorBag();
    }

    public function selectInventory(int $id, string $mode = 'in'): void
    {
        $this->inventoryId = (string) $id;
        $this->mode = $mode;
        $this->qty = '';
        $this->note = '';
        $this->tab = 'levels';
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $inventory = Inventory::findOrFail($id);

        $this->editingId = $inventory->id;
        $this->name = $inventory->name;
        $this->category = $inventory->category;
        $this->quantity = (string) $inventory->quantity;
        $this->unit = $inventory->unit;
        $this->reorderLevel = (string) $inventory->reorder_level;
        $this->costPerUnit = (string) ($inventory->cost_per_unit ?? '0.00');
        $this->isActive = $inventory->is_active;
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    public function saveIngredient(InventoryService $inventoryService): void
    {
        $validated = $this->validate([
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('inventories', 'name')->ignore($this->editingId),
            ],
            'category' => ['required', 'string', Rule::in(Inventory::CATEGORIES)],
            'quantity' => [$this->editingId ? 'nullable' : 'required', 'numeric', 'min:0', 'max:999999999'],
            'unit' => ['required', 'string', Rule::in(Inventory::UNITS)],
            'reorderLevel' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'costPerUnit' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'isActive' => ['boolean'],
        ]);

        $payload = [
            'name' => trim($validated['name']),
            'category' => $validated['category'],
            'unit' => $validated['unit'],
            'reorder_level' => round((float) $validated['reorderLevel'], 3),
            'cost_per_unit' => $validated['costPerUnit'] !== null && $validated['costPerUnit'] !== ''
                ? round((float) $validated['costPerUnit'], 2)
                : null,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            $inventory = Inventory::findOrFail($this->editingId);
            $inventory->update($payload);
            $message = "\"{$inventory->name}\" was updated.";
        } else {
            $inventory = Inventory::create($payload + ['quantity' => 0]);

            $opening = round((float) $this->quantity, 3);

            if ($opening > 0) {
                $inventoryService->receive($inventory, $opening, auth()->user(), 'Opening stock');
            }

            $message = "\"{$inventory->name}\" was added to inventory.";
        }

        $this->resetForm();
        session()->flash('status', $message);
    }

    public function toggleIngredient(int $id): void
    {
        $inventory = Inventory::findOrFail($id);
        $inventory->update(['is_active' => ! $inventory->is_active]);

        session()->flash('status', $inventory->is_active
            ? "\"{$inventory->name}\" is active."
            : "\"{$inventory->name}\" was archived.");
    }

    public function deleteIngredient(int $id): void
    {
        $inventory = Inventory::withCount('movements')->findOrFail($id);

        if ($inventory->movements_count > 0) {
            session()->flash('error', "Archive \"{$inventory->name}\" instead — it already has movement history.");

            return;
        }

        $inventory->delete();
        session()->flash('status', "\"{$inventory->name}\" was deleted.");
    }

    public function saveMovement(InventoryService $inventoryService): void
    {
        $validated = $this->validate([
            'inventoryId' => ['required', 'integer', Rule::exists('inventories', 'id')],
            'mode' => ['required', Rule::in(['in', 'adjust', 'waste'])],
            'qty' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $inventory = Inventory::findOrFail($validated['inventoryId']);
        $qty = round((float) $validated['qty'], 3);

        if ($validated['mode'] !== 'adjust' && $qty <= 0) {
            $this->addError('qty', 'Enter a quantity greater than zero.');

            return;
        }

        $unit = $inventory->unit;

        $message = match ($validated['mode']) {
            'in' => tap("Received {$qty} {$unit} of {$inventory->name}.", fn () => $inventoryService->receive($inventory, $qty, auth()->user(), $validated['note'] ?: null)),
            'adjust' => tap("{$inventory->name} count set to {$qty} {$unit}.", fn () => $inventoryService->adjustTo($inventory, $qty, auth()->user(), $validated['note'] ?: null)),
            'waste' => tap("Recorded {$qty} {$unit} of {$inventory->name} as wastage.", fn () => $inventoryService->wastage($inventory, $qty, auth()->user(), $validated['note'] ?: null)),
        };

        $this->reset('qty', 'note');
        session()->flash('status', $message);
    }

    public function render()
    {
        $levels = Inventory::query()
            ->withMax('movements', 'created_at')
            ->orderBy('category')
            ->orderBy('name');

        if (trim($this->search) !== '') {
            $term = '%'.mb_strtolower(trim($this->search)).'%';
            $levels->where(function ($builder) use ($term) {
                $builder->whereRaw('lower(name) like ?', [$term])
                    ->orWhereRaw('lower(category) like ?', [$term]);
            });
        }

        if ($this->categoryFilter !== 'all') {
            $levels->where('category', $this->categoryFilter);
        }

        $movements = InventoryMovement::query()
            ->with(['inventory', 'user'])
            ->latest()
            ->limit(60);

        if ($this->logType !== 'all') {
            $movements->where('type', $this->logType);
        }

        $ingredients = Inventory::query()
            ->active()
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $stockValue = Inventory::query()
            ->active()
            ->get()
            ->sum(fn (Inventory $inventory) => $inventory->stockValueCents());

        return view('livewire.inventory.index', [
            'levels' => $levels->limit(200)->get(),
            'movements' => $movements->get(),
            'ingredients' => $ingredients,
            'selectedInventory' => $this->inventoryId
                ? $ingredients->firstWhere('id', (int) $this->inventoryId)
                : null,
            'categories' => Inventory::CATEGORIES,
            'units' => Inventory::UNITS,
            'trackedCount' => Inventory::query()->active()->count(),
            'lowCount' => Inventory::query()->active()->lowStock()->count(),
            'outCount' => Inventory::query()->active()->where('quantity', '<=', 0)->count(),
            'stockValue' => $stockValue,
        ])->layout('layouts.app', [
            'title' => 'Inventory',
            'subtitle' => 'Ingredients, supplies and kitchen stock',
        ]);
    }

    private function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->name = '';
        $this->category = Inventory::CATEGORIES[0];
        $this->quantity = '0';
        $this->unit = 'kg';
        $this->reorderLevel = '0';
        $this->costPerUnit = '0.00';
        $this->isActive = true;
        $this->resetErrorBag();
    }
}
