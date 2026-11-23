<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportExportController;
use App\Livewire\Categories\Index as CategoriesIndex;
use App\Livewire\Dashboard;
use App\Livewire\Inventory\Index as InventoryIndex;
use App\Livewire\Pos\Terminal;
use App\Livewire\Products\Form as ProductForm;
use App\Livewire\Products\Index as ProductsIndex;
use App\Livewire\Reports\Daily;
use App\Livewire\Sales\Index as SalesIndex;
use App\Livewire\Settings\Edit as SettingsEdit;
use App\Livewire\Shifts\Index as ShiftsIndex;
use App\Livewire\Users\Index as UsersIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return redirect()->route(auth()->user()->homeRoute());
});

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Till — admins and cashiers
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin,cashier'])->group(function () {
    Route::get('pos', Terminal::class)->name('pos');
    Route::get('sales/{sale}/receipt', [ReceiptController::class, 'show'])->name('sales.receipt');
});

/*
|--------------------------------------------------------------------------
| Back office — admins only
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    Route::get('products', ProductsIndex::class)->name('products.index');
    Route::get('products/create', ProductForm::class)->name('products.create');
    Route::get('products/{product}/edit', ProductForm::class)->name('products.edit');

    Route::get('categories', CategoriesIndex::class)->name('categories.index');
    Route::get('inventory', InventoryIndex::class)->name('inventory.index');

    Route::get('sales', SalesIndex::class)->name('sales.index');
    Route::get('shifts', ShiftsIndex::class)->name('shifts.index');

    Route::get('reports/daily', Daily::class)->name('reports.daily');
    Route::get('reports/daily/export', [ReportExportController::class, 'daily'])->name('reports.daily.export');

    Route::get('users', UsersIndex::class)->name('users.index');
    Route::get('settings', SettingsEdit::class)->name('settings.edit');
});
