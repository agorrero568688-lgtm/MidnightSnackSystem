<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Index extends Component
{
    public string $name = '';

    public ?int $editingId = null;

    public string $editingName = '';

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
        ]);

        Category::create([
            'name' => $validated['name'],
            'sort_order' => (int) Category::max('sort_order') + 1,
        ]);

        $this->reset('name');
        session()->flash('status', 'Category added.');
    }

    public function startEdit(int $id): void
    {
        $category = Category::findOrFail($id);
        $this->editingId = $category->id;
        $this->editingName = $category->name;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->editingName = '';
    }

    public function update(): void
    {
        if (! $this->editingId) {
            return;
        }

        $validated = $this->validate([
            'editingName' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($this->editingId)],
        ]);

        Category::findOrFail($this->editingId)->update(['name' => $validated['editingName']]);

        $this->cancelEdit();
        session()->flash('status', 'Category renamed.');
    }

    public function toggle(int $id): void
    {
        $category = Category::findOrFail($id);
        $category->update(['is_active' => ! $category->is_active]);
    }

    public function delete(int $id): void
    {
        $category = Category::withCount('products')->findOrFail($id);

        if ($category->products_count > 0) {
            session()->flash('error', "Move the {$category->products_count} product(s) in \"{$category->name}\" first.");

            return;
        }

        $category->delete();
        session()->flash('status', 'Category deleted.');
    }

    public function render()
    {
        return view('livewire.categories.index', [
            'categories' => Category::withCount('products')->orderBy('sort_order')->orderBy('name')->get(),
        ])->layout('layouts.app', ['title' => 'Categories', 'subtitle' => 'Group products for faster scanning']);
    }
}
