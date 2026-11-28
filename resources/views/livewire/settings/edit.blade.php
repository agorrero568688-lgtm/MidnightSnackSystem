<form wire:submit="save" class="space-y-5">
    @if ($notice)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ $notice }}</div>
    @endif

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="card">
                <div class="card-header"><h2 class="text-sm font-bold">Store details</h2></div>
                <div class="card-body grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="label">Store name</label>
                        <input wire:model="storeName" type="text" class="input">
                        @error('storeName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Address</label>
                        <input wire:model="storeAddress" type="text" class="input" placeholder="Street, barangay, city">
                        @error('storeAddress') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Phone / contact</label>
                        <input wire:model="storePhone" type="text" class="input" placeholder="0917 000 0000">
                        @error('storePhone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="text-sm font-bold">Receipts</h2></div>
                <div class="card-body grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Paper width</label>
                        <select wire:model="receiptWidth" class="input">
                            <option value="58">58 mm thermal</option>
                            <option value="80">80 mm thermal</option>
                        </select>
                        @error('receiptWidth') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Footer message</label>
                        <input wire:model="receiptFooter" type="text" class="input" placeholder="Salamat po! Come again.">
                        @error('receiptFooter') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="text-sm font-bold">Cashier behaviour</h2></div>
                <div class="card-body space-y-4">
                    <label class="flex items-start gap-3">
                        <input wire:model="requireShift" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-ink-300 text-brand-500">
                        <span>
                            <span class="block text-sm font-medium">Require a shift before selling</span>
                            <span class="block text-xs text-ink-500">Cashiers open a drawer session with their starting cash, and close it with a count.</span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="text-sm font-bold">Online ordering</h2>
                    <span class="badge {{ $onlineOrderingEnabled && ! $ordersPaused ? 'badge-green' : 'badge-amber' }}">
                        {{ $onlineOrderingEnabled ? ($ordersPaused ? 'Paused' : 'Live') : 'Closed' }}
                    </span>
                </div>
                <div class="card-body space-y-4">
                    <label class="flex items-start gap-3">
                        <input wire:model="onlineOrderingEnabled" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-ink-300 text-brand-500">
                        <span>
                            <span class="block text-sm font-medium">Show the public menu and accept orders</span>
                            <span class="block text-xs text-ink-500">The storefront is always visible, but ordering closes when this is off.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3">
                        <input wire:model="ordersPaused" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-ink-300 text-brand-500">
                        <span>
                            <span class="block text-sm font-medium">Pause new orders for now</span>
                            <span class="block text-xs text-ink-500">Use this when the kitchen is slammed. Existing orders keep moving.</span>
                        </span>
                    </label>

                    <div class="grid gap-4 border-t border-ink-100 pt-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="label">Store hours shown to customers</label>
                            <input wire:model="storeHours" type="text" class="input" placeholder="Open 24/7">
                            @error('storeHours') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label">Delivery fee (other barangays)</label>
                            <div class="relative">
                                <span class="absolute top-1/2 left-3 -translate-y-1/2 text-sm text-ink-400">₱</span>
                                <input wire:model="deliveryFee" type="number" step="0.01" min="0" class="input pl-7">
                            </div>
                            @error('deliveryFee') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="label">Free delivery barangays</label>
                            <textarea wire:model="deliveryFreeBarangays" rows="3" maxlength="400" class="input" spellcheck="false"
                                placeholder="Magosilom&#10;Lininti-an"></textarea>
                            <p class="mt-1 text-[11px] text-ink-500">One barangay per line — customers typing an address with one of these names get free delivery.</p>
                            @error('deliveryFreeBarangays') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label">Free delivery from</label>
                            <div class="relative">
                                <span class="absolute top-1/2 left-3 -translate-y-1/2 text-sm text-ink-400">₱</span>
                                <input wire:model="freeDeliveryThreshold" type="number" step="0.01" min="0" class="input pl-7">
                            </div>
                            <p class="mt-1 text-[11px] text-ink-500">Set to 0.00 to always charge the delivery fee.</p>
                            @error('freeDeliveryThreshold') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label">Minimum order</label>
                            <div class="relative">
                                <span class="absolute top-1/2 left-3 -translate-y-1/2 text-sm text-ink-400">₱</span>
                                <input wire:model="minimumOrderTotal" type="number" step="0.01" min="0" class="input pl-7">
                            </div>
                            <p class="mt-1 text-[11px] text-ink-500">Based on the item subtotal, before delivery.</p>
                            @error('minimumOrderTotal') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-3 border-t border-ink-100 pt-4 sm:grid-cols-2">
                        <label class="flex items-start gap-3">
                            <input wire:model="deliveryEnabled" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-ink-300 text-brand-500">
                            <span>
                                <span class="block text-sm font-medium">Offer delivery</span>
                                <span class="block text-xs text-ink-500">Customers enter an address and pay the delivery fee.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3">
                            <input wire:model="pickupEnabled" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-ink-300 text-brand-500">
                            <span>
                                <span class="block text-sm font-medium">Offer pickup</span>
                                <span class="block text-xs text-ink-500">No delivery fee; the customer collects at the counter.</span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="text-sm font-bold">GCash details</h2></div>
                <div class="card-body space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Account name</label>
                            <input wire:model="gcashName" type="text" class="input" placeholder="Diding's Midnight Snack">
                            @error('gcashName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">GCash number</label>
                            <input wire:model="gcashNumber" type="text" class="input font-mono" placeholder="0917 123 4567">
                            @error('gcashNumber') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="label">GCash QR image <span class="text-ink-400 normal-case">(optional)</span></label>
                        @php
                            $existingQrUrl = \App\Support\GcashQr::urlFor($existingGcashQrPath);
                        @endphp
                        <div class="flex flex-wrap items-start gap-4">
                            @if ($gcashQr)
                                <x-gcash-qr :src="$gcashQr->temporaryUrl()" alt="New GCash QR" caption="Tap to preview" />
                            @elseif ($existingQrUrl)
                                <x-gcash-qr :src="$existingQrUrl" />
                            @elseif ($existingGcashQrPath)
                                <div class="flex h-40 w-40 flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-rose-300 bg-rose-50 p-3 text-center text-[10px] font-semibold text-rose-600">
                                    <x-icon name="alert" class="h-5 w-5" />
                                    Saved file is missing — re-upload
                                </div>
                            @else
                                <div class="flex h-40 w-40 items-center justify-center rounded-xl border border-dashed border-ink-300 bg-ink-50 p-3 text-center text-[10px] text-ink-400">
                                    No QR yet
                                </div>
                            @endif

                            <div class="min-w-48 flex-1 space-y-2">
                                <input wire:model="gcashQr" type="file" accept="image/*" class="block w-full text-xs text-ink-600 file:mr-3 file:rounded-lg file:border-0 file:bg-ink-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-ink-700">
                                <p class="text-[11px] text-ink-500">PNG or JPG, up to 2 MB. Shown at checkout and on the tracking page — customers can tap it to enlarge.</p>
                                @error('gcashQr') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror

                                <div class="border-t border-ink-100 pt-2">
                                    <label class="label">Your password <span class="text-ink-400 normal-case">(required for GCash changes)</span></label>
                                    <input wire:model="confirmPassword" type="password" class="input" autocomplete="current-password" placeholder="Enter your password to save payment changes">
                                    @error('confirmPassword') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                </div>

                                @if ($existingGcashQrPath)
                                    <button type="button" wire:click="removeGcashQr" class="btn btn-sm text-rose-600 hover:bg-rose-50">Remove QR</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="text-sm font-bold">Order alerts</h2></div>
                <div class="card-body space-y-4">
                    <label class="flex items-start gap-3">
                        <input wire:model="orderAlertsEnabled" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-ink-300 text-brand-500">
                        <span>
                            <span class="block text-sm font-medium">Send new orders via SMS to admin phone number(s)</span>
                            <span class="block text-xs text-ink-500">The board still beeps and shows browser notifications without this.</span>
                        </span>
                    </label>

                    <div class="border-t border-ink-100 pt-4">
                        <label class="label">Admin phone number(s)</label>
                        <textarea wire:model="orderAlertPhones" rows="2" class="input font-mono" placeholder="09171234567, 09181234567"></textarea>
                        <p class="mt-1 text-[11px] text-ink-500">Separate multiple numbers with commas or new lines.</p>
                        @error('orderAlertPhones') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" wire:click="testOrderAlert" wire:loading.attr="disabled" class="btn btn-secondary btn-sm">Send test alert SMS</button>
                        @if ($orderAlertStatus)
                            <p class="text-xs font-semibold {{ str_contains($orderAlertStatus, 'sent') ? 'text-emerald-700' : 'text-rose-700' }}">{{ $orderAlertStatus }}</p>
                        @endif
                    </div>

                    <p class="rounded-xl bg-ink-50 p-3 text-[11px] leading-relaxed text-ink-500">
                        Admin alerts use the SMS gateway configured below (even if customer SMS updates are turned off).
                        Alerts are queued, so run a queue worker in production (<span class="font-mono">php artisan queue:work</span>).
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="text-sm font-bold">SMS gateway &amp; customer updates</h2></div>
                <div class="card-body space-y-4">
                    <label class="flex items-start gap-3">
                        <input wire:model="smsEnabled" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-ink-300 text-brand-500">
                        <span>
                            <span class="block text-sm font-medium">Send SMS updates to customers</span>
                            <span class="block text-xs text-ink-500">Ready, abandoned-order reminders and feedback requests.</span>
                        </span>
                    </label>

                    <div class="grid gap-4 border-t border-ink-100 pt-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Gateway URL</label>
                            <input wire:model="smsGatewayUrl" type="url" placeholder="https://api.gateway.com/sms" class="input">
                            @error('smsGatewayUrl') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label">API key</label>
                            <input wire:model="smsGatewayKey" type="password" class="input" autocomplete="off">
                            @error('smsGatewayKey') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label">Sender name</label>
                            <input wire:model="smsSenderName" type="text" maxlength="12" class="input">
                            @error('smsSenderName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label">Reminder wait (minutes)</label>
                            <input wire:model="smsReminderMinutes" type="number" min="5" max="120" class="input w-24">
                            @error('smsReminderMinutes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label">Feedback every N orders</label>
                            <input wire:model="smsFeedbackEveryN" type="number" min="1" max="10" class="input w-24">
                            @error('smsFeedbackEveryN') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="button" wire:click="testSms" wire:loading.attr="disabled" class="btn btn-secondary btn-sm">Send test SMS</button>
                        @if ($smsStatus)
                            <p class="text-xs font-semibold {{ str_contains($smsStatus, 'sent') ? 'text-emerald-700' : 'text-rose-700' }}">{{ $smsStatus }}</p>
                        @endif
                    </div>

                    <p class="rounded-xl bg-ink-50 p-3 text-[11px] leading-relaxed text-ink-500">
                        The gateway URL and API key are shared by admin order alerts and customer updates. The API key is
                        sent as an <span class="font-mono">Authorization: Bearer</span> header when set. Customer SMS only
                        sends while the checkbox above is ticked.
                    </p>
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="card card-body space-y-2 text-sm text-ink-600">
                <p class="font-semibold text-ink-800">Receipt preview</p>
                <p class="text-xs">Changes apply to new receipts. Reopening an old receipt link re-renders with the current store details.</p>
                <div class="mt-2 rounded-xl border border-dashed border-ink-300 bg-ink-50 p-3 font-mono text-[11px] leading-relaxed">
                    <p class="text-center font-bold">{{ $storeName }}</p>
                    @if ($storeAddress) <p class="text-center">{{ $storeAddress }}</p> @endif
                    @if ($storePhone) <p class="text-center">{{ $storePhone }}</p> @endif
                    <p class="my-1 border-t border-dashed border-ink-300"></p>
                    <p>Ref: DMS-000123</p>
                    <p>Cashier: {{ auth()->user()->name }}</p>
                    <p class="my-1 border-t border-dashed border-ink-300"></p>
                    <p class="flex justify-between"><span>2 x Chips</span><span>₱50.00</span></p>
                    <p class="flex justify-between font-bold"><span>TOTAL</span><span>₱50.00</span></p>
                    <p class="my-1 border-t border-dashed border-ink-300"></p>
                    <p class="text-center">{{ $receiptFooter }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end">
        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
            <span wire:loading.remove>Save settings</span>
            <span wire:loading>Saving…</span>
        </button>
    </div>
</form>
