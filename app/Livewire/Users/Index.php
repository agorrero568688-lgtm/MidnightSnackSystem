<?php

namespace App\Livewire\Users;

use App\Enums\Role;
use App\Models\LoginLog;
use App\Models\User;
use App\Support\Concerns\ConfirmsWithPassword;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    use ConfirmsWithPassword;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $username = '';

    public string $password = '';

    public string $role = 'cashier';

    public bool $isActive = true;

    #[Locked]
    public ?string $originalRole = null;

    #[Locked]
    public bool $originalIsActive = true;

    public function startCreate(): void
    {
        $this->reset('name', 'username', 'password', 'editingId');
        $this->role = Role::Cashier->value;
        $this->isActive = true;
        $this->originalRole = null;
        $this->originalIsActive = true;
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function startEdit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->role = $user->role->value;
        $this->isActive = $user->is_active;
        $this->originalRole = $this->role;
        $this->originalIsActive = $this->isActive;
        $this->password = '';
        $this->showForm = true;
        $this->resetErrorBag();
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->resetErrorBag();
    }

    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'username' => [
                'required', 'string', 'max:50', 'alpha_dash',
                Rule::unique('users', 'username')->ignore($this->editingId),
            ],
            'role' => ['required', Rule::in(array_column(Role::cases(), 'value'))],
            'isActive' => ['boolean'],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8'],
        ];

        $validated = $this->validate($rules);

        if ($this->editingId === auth()->id() && ($validated['role'] !== Role::Admin->value || ! $this->isActive)) {
            $this->addError('role', 'You cannot demote or deactivate your own account.');

            return;
        }

        $sensitiveChange = $this->editingId === null
            || $validated['role'] !== $this->originalRole
            || filled($validated['password'])
            || $this->isActive !== $this->originalIsActive;

        if ($sensitiveChange && ! $this->confirmPasswordOrFail()) {
            return;
        }

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->fill([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'role' => $validated['role'],
                'is_active' => $this->isActive,
            ]);

            if (filled($validated['password'])) {
                $user->password = $validated['password'];
            }

            $user->save();
            $message = "{$user->name}'s account was updated.";
        } else {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'role' => $validated['role'],
                'is_active' => $this->isActive,
                'password' => $validated['password'],
            ]);
            $message = "{$user->name} can now sign in as a {$user->role->label()}.";
        }

        $this->cancel();
        session()->flash('status', $message);
    }

    public function toggleActive(int $id): void
    {
        if ($id === auth()->id()) {
            session()->flash('error', 'You cannot deactivate your own account.');

            return;
        }

        if (! $this->confirmPasswordOrFail()) {
            return;
        }

        $user = User::findOrFail($id);
        $user->update(['is_active' => ! $user->is_active]);

        session()->flash('status', $user->is_active
            ? "{$user->name} can sign in again."
            : "{$user->name} was deactivated.");
    }

    public function render()
    {
        return view('livewire.users.index', [
            'users' => User::query()->withCount('sales')->orderBy('name')->get(),
            'roles' => Role::options(),
            'loginLogs' => LoginLog::with('user')->latest('created_at')->limit(20)->get(),
        ])->layout('layouts.app', ['title' => 'Users', 'subtitle' => 'Admins and cashiers']);
    }
}
