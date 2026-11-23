<?php

namespace App\Livewire\Shifts;

use App\Models\Shift;
use App\Support\Money;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        $this->from = today()->subDays(6)->toDateString();
        $this->to = today()->toDateString();
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $shifts = Shift::query()
            ->with('user')
            ->when($this->from, fn ($builder) => $builder->whereDate('opened_at', '>=', $this->from))
            ->when($this->to, fn ($builder) => $builder->whereDate('opened_at', '<=', $this->to))
            ->orderByDesc('opened_at')
            ->paginate(20);

        $totals = Shift::query()
            ->when($this->from, fn ($builder) => $builder->whereDate('opened_at', '>=', $this->from))
            ->when($this->to, fn ($builder) => $builder->whereDate('opened_at', '<=', $this->to))
            ->get();

        return view('livewire.shifts.index', [
            'shifts' => $shifts,
            'openCount' => $totals->filter(fn (Shift $shift) => $shift->isOpen())->count(),
            'totalVarianceCents' => $totals->sum(fn (Shift $shift) => Money::cents($shift->variance ?? 0)),
        ])->layout('layouts.app', ['title' => 'Shifts', 'subtitle' => 'Cash drawer sessions and variances']);
    }
}
