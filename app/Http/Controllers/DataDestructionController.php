<?php

namespace App\Http\Controllers;

use App\Support\PosCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class DataDestructionController extends Controller
{
    /** The only value that satisfies the destructive-delete confirmation. */
    public const CONFIRMATION_PHRASE = 'DELETE ALL DATA';

    /**
     * Tables deleted, in a safe order: children before parents, users last
     * because other rows still reference them until the end of the transaction.
     */
    private const TABLES = [
        'orders',
        'order_items',
        'sale_items',
        'sales',
        'shifts',
        'stock_movements',
        'products',
        'categories',
        'users',
        'settings',
    ];

    private const PUBLIC_DIRECTORIES = [
        'products',
        'store-settings',
        'order-proofs',
    ];

    private const PRIVATE_DIRECTORIES = [
        'order-proofs',
    ];

    /** @return View */
    public function index()
    {
        return view('admin.destruct', ['counts' => $this->counts()]);
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        return array_map(
            fn (string $table): int => Schema::hasTable($table) ? (int) DB::table($table)->count() : 0,
            static::TABLES,
        );
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'confirmation' => [
                'required',
                'string',
                'max:32',
                function ($attribute, $value, $fail): void {
                    if (trim((string) $value) !== self::CONFIRMATION_PHRASE) {
                        $fail('Type the exact phrase “'.self::CONFIRMATION_PHRASE.'” — nothing is deleted until then.');
                    }
                },
            ],
        ]);

        $deleted = $this->deleteAll();

        $summary = collect($deleted)
            ->filter(fn (int $count): bool => $count > 0)
            ->map(fn (int $count, string $table): string => $table.': '.$count)
            ->join(', ');

        session()->flash('data_deleted', 'Everything was deleted permanently.'
            .($summary !== '' ? ' Removed '.$summary.'.' : '')
            .' The database and files are now empty.');

        return redirect()->route('data.destruct');
    }

    /** @return array<string, int> */
    private function deleteAll(): array
    {
        $deleted = $this->counts();

        try {
            DB::beginTransaction();

            foreach (static::TABLES as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                DB::table($table)->delete();
            }

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }

        foreach (static::PUBLIC_DIRECTORIES as $directory) {
            Storage::disk('public')->deleteDirectory($directory);
        }

        foreach (static::PRIVATE_DIRECTORIES as $directory) {
            Storage::disk('local')->deleteDirectory($directory);
        }

        PosCatalog::flush();

        return $deleted;
    }
}
