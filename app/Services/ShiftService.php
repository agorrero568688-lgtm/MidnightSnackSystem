<?php

namespace App\Services;

use App\Enums\ShiftStatus;
use App\Models\Shift;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftService
{
    /** @throws ValidationException */
    public function open(User $user, string|int|float|null $openingCash = 0): Shift
    {
        if ($user->currentShift()) {
            throw ValidationException::withMessages(['shift' => 'You already have an open shift.']);
        }

        return Shift::create([
            'user_id' => $user->id,
            'status' => ShiftStatus::Open,
            'opened_at' => now(),
            'opening_cash' => Money::decimal(Money::cents($openingCash)),
        ]);
    }

    /** @throws ValidationException */
    public function close(Shift $shift, string|int|float|null $countedCash, ?string $notes = null): Shift
    {
        return DB::transaction(function () use ($shift, $countedCash, $notes) {
            /** @var Shift $locked */
            $locked = Shift::query()->whereKey($shift->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isOpen()) {
                throw ValidationException::withMessages(['shift' => 'This shift is already closed.']);
            }

            $expected = $locked->expectedCashCents();
            $counted = Money::cents($countedCash);

            $locked->forceFill([
                'status' => ShiftStatus::Closed,
                'closed_at' => now(),
                'closing_cash' => Money::decimal($counted),
                'expected_cash' => Money::decimal($expected),
                'variance' => Money::decimal($counted - $expected),
                'notes' => $notes,
            ])->save();

            return $locked;
        });
    }
}
