<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\User;
use App\Support\PosCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    /**
     * Void a completed sale.
     *
     * Menu items no longer carry stock, so voiding only changes the sale
     * status. Ingredient stock is managed separately in the inventory module.
     *
     * @throws ValidationException
     */
    public function void(Sale $sale, User $user, string $reason): Sale
    {
        $voided = DB::transaction(function () use ($sale, $user, $reason) {
            /** @var Sale $locked */
            $locked = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== SaleStatus::Completed) {
                throw ValidationException::withMessages(['sale' => 'This sale has already been voided.']);
            }

            $locked->forceFill([
                'status' => SaleStatus::Voided,
                'voided_by' => $user->id,
                'voided_at' => now(),
                'void_reason' => $reason,
            ])->save();

            return $locked->load('items');
        }, 3);

        PosCatalog::flush();

        return $voided;
    }
}
