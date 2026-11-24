<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaceOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\StorefrontService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderingController extends Controller
{
    public function __construct(private readonly StorefrontService $storefront) {}

    public function index(): View
    {
        $data = $this->storefront->data();

        return view('orders.storefront', [
            'sections' => $data['sections'],
            'products' => $data['products'],
            'orderingOpen' => $data['orderingOpen'],
            'config' => $data['bootstrap']['config'],
            'bootstrap' => $data['bootstrap'],
        ]);
    }

    public function checkout(): View
    {
        $data = $this->storefront->data();

        return view('orders.checkout', [
            'products' => $data['products'],
            'orderingOpen' => $data['orderingOpen'],
            'config' => $data['bootstrap']['config'],
            'bootstrap' => $data['bootstrap'],
        ]);
    }

    public function store(PlaceOrderRequest $request, OrderService $orders): JsonResponse|RedirectResponse
    {
        $order = $orders->place($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'reference' => $order->reference_no,
                'redirect' => route('orders.track', ['token' => $order->public_token]),
            ]);
        }

        return redirect()
            ->route('orders.track', ['token' => $order->public_token])
            ->with('status', "Order {$order->reference_no} received. Keep this page to follow it.");
    }

    public function track(string $token): View
    {
        $order = Order::query()
            ->with(['items', 'sale', 'paymentVerifiedBy'])
            ->where('public_token', $token)
            ->firstOrFail();

        return view('orders.track', [
            'order' => $order,
            'config' => $this->storefront->storeConfig(),
        ]);
    }

    public function lookupForm(): View
    {
        return view('orders.lookup', [
            'config' => $this->storefront->storeConfig(),
        ]);
    }

    /**
     * Repeat customers: the items they have ordered most, to re-offer in the cart.
     *
     * @return JsonResponse
     */
    public function suggestions()
    {
        $products = app(OrderService::class)->suggestionsForPhone(trim((string) request('phone', '')));

        $data = $products->map(fn (Product $product) => [
            'id' => $product->id,
            'name' => $product->name,
            'price_cents' => Money::cents($product->selling_price),
            'description' => $product->description,
        ])->values();

        return response()->json($data);
    }

    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:30'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $order = Order::query()
            ->whereRaw('upper(reference_no) = ?', [mb_strtoupper(trim($validated['reference']))])
            ->first();

        $phone = preg_replace('/\D+/', '', $validated['phone']);
        $storedPhone = $order ? preg_replace('/\D+/', '', $order->customer_phone) : null;

        if (! $order || $phone === '' || $phone !== $storedPhone) {
            return back()
                ->withInput()
                ->withErrors(['reference' => 'We could not find an order with that reference and phone number.']);
        }

        return redirect()->route('orders.track', ['token' => $order->public_token]);
    }

    public function updatePayment(Request $request, string $token, OrderService $orders): RedirectResponse
    {
        $order = Order::query()->where('public_token', $token)->firstOrFail();

        $validated = $request->validate([
            'proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $orders->submitPayment($order, $validated['proof']);

        return redirect()
            ->route('orders.track', ['token' => $order->public_token])
            ->with('status', 'Receipt received. We will verify your GCash payment shortly.');
    }
}
