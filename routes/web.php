<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GcashQrController;
use App\Http\Controllers\OrderingController;
use App\Http\Controllers\OrderProofController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportExportController;
use App\Livewire\Categories\Index as CategoriesIndex;
use App\Livewire\Dashboard;
use App\Livewire\Inventory\Index as InventoryIndex;
use App\Livewire\Orders\Board as OrdersBoard;
use App\Livewire\Pos\Terminal;
use App\Livewire\Products\Form as ProductForm;
use App\Livewire\Products\Index as ProductsIndex;
use App\Livewire\Reports\Daily;
use App\Livewire\Sales\Index as SalesIndex;
use App\Livewire\Settings\Edit as SettingsEdit;
use App\Livewire\Shifts\Index as ShiftsIndex;
use App\Livewire\Users\Index as UsersIndex;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public storefront and online ordering
|--------------------------------------------------------------------------
*/
Route::get('/', [OrderingController::class, 'index'])->name('storefront');
Route::get('order', [OrderingController::class, 'checkout'])->name('orders.checkout');

Route::post('orders', [OrderingController::class, 'store'])
    ->middleware('throttle:12,1')
    ->name('orders.store');

Route::get('track', [OrderingController::class, 'lookupForm'])->name('orders.lookup');
Route::post('track', [OrderingController::class, 'lookup'])
    ->middleware('throttle:20,1')
    ->name('orders.lookup.submit');

Route::get('track/{token}', [OrderingController::class, 'track'])->name('orders.track');
Route::post('track/{token}/payment', [OrderingController::class, 'updatePayment'])
    ->middleware('throttle:12,1')
    ->name('orders.payment');

Route::get('track/{token}/proof', [OrderProofController::class, 'showByToken'])
    ->name('orders.proof.public');

Route::get('suggestions', [OrderingController::class, 'suggestions'])->name('suggestions');

Route::get('gcash-qr', [GcashQrController::class, 'show'])->name('gcash.qr');

Route::get('cron/abandoned-reminders', [CronController::class, 'abandonedReminders'])
    ->name('cron.abandoned-reminders');

Route::get('feedback/{token}', [FeedbackController::class, 'show'])->name('feedback.show');
Route::post('feedback/{token}', [FeedbackController::class, 'update'])->name('feedback.update');

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
    Route::get('orders', OrdersBoard::class)->name('orders.index');
    Route::get('orders/{order}/proof', [OrderProofController::class, 'show'])->name('orders.proof');

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
