<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Support\PosCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(private readonly StockService $stock) {}

    /**
     * Void a completed sale and put the stock back.
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

            $items = $locked->items()->whereNotNull('product_id')->get();

            $products = Product::query()
                ->whereIn('id', $items->pluck('product_id')->unique())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $product = $products->get($item->product_id);

                if (! $product) {
                    continue;
                }

                $this->stock->record(
                    $product,
                    StockMovementType::Void,
                    $item->qty,
                    $user,
                    "Void {$locked->reference_no}",
                    'sale',
                    $locked->id,
                );
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
