/**
 * Public storefront cart and checkout state.
 *
 * Prices in the cart are display-only. The server re-reads every price and
 * re-checks stock when the order is placed.
 */
window.orderCart = function () {
    const storageKey = 'dms.cart.v1';

    return {
        ready: false,
        products: {},
        config: {},
        items: [],
        open: false,
        toast: '',
        search: '',
        activeCategory: 'all',

        orderType: 'delivery',
        paymentMethod: 'gcash',
        customerName: '',
        customerPhone: '',
        customerEmail: '',
        address: '',
        notes: '',
        reference: '',

        errors: {},
        formError: '',
        submitting: false,
        copied: false,

        init() {
            const node = document.getElementById('order-bootstrap');

            if (node) {
                try {
                    const data = JSON.parse(node.textContent);
                    this.products = data.products || {};
                    this.config = data.config || {};
                } catch (error) {
                    console.error('Could not read the menu data.', error);
                }
            }

            this.orderType = this.config.delivery_enabled ? 'delivery' : 'pickup';
            this.load();
            this.ready = true;
            this.$watch('items', () => this.persist(), { deep: true });
        },

        /* ------------------------------------------------------------------ */
        /* Cart                                                               */
        /* ------------------------------------------------------------------ */

        load() {
            try {
                const saved = JSON.parse(localStorage.getItem(storageKey) || '[]');

                if (!Array.isArray(saved)) {
                    return;
                }

                this.items = saved
                    .filter((line) => this.products[line.id] && this.products[line.id].available)
                    .map((line) => {
                        const product = this.products[line.id];
                        const max = Math.max(1, product.max_qty || 20);
                        const qty = Math.min(max, Math.max(1, Number(line.qty) || 1));

                        return {
                            id: product.id,
                            name: product.name,
                            price_cents: product.price_cents,
                            qty,
                            note: String(line.note || '').slice(0, 200),
                        };
                    });
            } catch (error) {
                this.items = [];
            }
        },

        persist() {
            try {
                localStorage.setItem(storageKey, JSON.stringify(this.items));
            } catch (error) {
                // Private mode or a full quota — the cart still works for this page.
            }
        },

        add(productOrId) {
            if (!this.config.ordering_open) {
                this.notify('Online ordering is closed right now.');
                return;
            }

            const id = typeof productOrId === 'object' ? productOrId.id : productOrId;
            const product = this.products[id] || (typeof productOrId === 'object' ? productOrId : null);

            if (!product || product.available === false) {
                this.notify('That item is sold out.');
                return;
            }

            const max = Math.max(1, product.max_qty || 20);
            const existing = this.items.find((line) => line.id === id);

            if (existing) {
                if (existing.qty >= max) {
                    this.notify('Only ' + max + ' available.');
                    return;
                }

                existing.qty += 1;
            } else {
                this.items.push({
                    id: product.id,
                    name: product.name,
                    price_cents: product.price_cents,
                    qty: 1,
                    note: '',
                });
            }

            this.notify(product.name + ' added');
        },

        inc(id) {
            const line = this.items.find((item) => item.id === id);

            if (!line) {
                return;
            }

            const product = this.products[id];
            const max = Math.max(1, (product && product.max_qty) || 20);

            if (line.qty < max) {
                line.qty += 1;
            } else {
                this.notify('Only ' + max + ' available.');
            }
        },

        dec(id) {
            const line = this.items.find((item) => item.id === id);

            if (!line) {
                return;
            }

            if (line.qty <= 1) {
                this.remove(id);
                return;
            }

            line.qty -= 1;
        },

        remove(id) {
            this.items = this.items.filter((item) => item.id !== id);
        },

        clearAll() {
            this.items = [];
            this.notify('Cart cleared');
        },

        qty(id) {
            const line = this.items.find((item) => item.id === id);

            return line ? line.qty : 0;
        },

        clear() {
            this.items = [];
        },

        get count() {
            return this.items.reduce((sum, item) => sum + item.qty, 0);
        },

        get subtotalCents() {
            return this.items.reduce((sum, item) => sum + item.price_cents * item.qty, 0);
        },

        get deliveryFeeCents() {
            if (this.orderType === 'pickup') {
                return 0;
            }

            const fee = Number(this.config.delivery_fee_cents || 0);
            const threshold = Number(this.config.free_delivery_threshold_cents || 0);

            if (threshold > 0 && this.subtotalCents >= threshold) {
                return 0;
            }

            return fee;
        },

        get totalCents() {
            return this.subtotalCents + this.deliveryFeeCents;
        },

        get belowMinimum() {
            const minimum = Number(this.config.minimum_order_cents || 0);

            return minimum > 0 && this.subtotalCents > 0 && this.subtotalCents < minimum;
        },

        get paymentOptions() {
            const options = [
                {
                    value: 'gcash',
                    label: 'GCash',
                    description: 'Pay now, then send us the reference number.',
                },
            ];

            if (this.orderType === 'delivery') {
                options.push({
                    value: 'cod',
                    label: 'Cash on delivery',
                    description: 'Pay the rider when your order arrives. We will call to confirm.',
                });
            } else {
                options.push({
                    value: 'cash',
                    label: 'Cash at pickup',
                    description: 'Pay at the counter when you collect your order.',
                });
            }

            return options;
        },

        setOrderType(type) {
            this.orderType = type;

            if (!this.paymentOptions.some((option) => option.value === this.paymentMethod)) {
                this.paymentMethod = 'gcash';
            }
        },

        openCart() {
            this.open = true;

            if (typeof document !== 'undefined') {
                document.body.style.overflow = 'hidden';
            }
        },

        closeCart() {
            this.open = false;

            if (typeof document !== 'undefined') {
                document.body.style.overflow = '';
            }
        },

        notify(message) {
            this.toast = message;

            if (this.toastTimer) {
                clearTimeout(this.toastTimer);
            }

            this.toastTimer = setTimeout(() => {
                this.toast = '';
            }, 1900);
        },

        copy(value) {
            if (!value || !navigator.clipboard) {
                return;
            }

            navigator.clipboard.writeText(value);
            this.copied = true;

            setTimeout(() => {
                this.copied = false;
            }, 1600);
        },

        /* ------------------------------------------------------------------ */
        /* Menu filters                                                       */
        /* ------------------------------------------------------------------ */

        productVisible(id) {
            const term = this.search.trim().toLowerCase();

            if (term === '') {
                return true;
            }

            const product = this.products[id];

            return product ? product.name.toLowerCase().includes(term) : false;
        },

        /* ------------------------------------------------------------------ */
        /* Checkout                                                           */
        /* ------------------------------------------------------------------ */

        error(field) {
            return this.errors[field] ? this.errors[field][0] : '';
        },

        validate() {
            this.errors = {};
            this.formError = '';

            if (!this.items.length) {
                this.formError = 'Your cart is empty.';
                return false;
            }

            if (this.belowMinimum) {
                this.formError = 'The minimum order is ' + this.money(this.config.minimum_order_cents) + '.';
                return false;
            }

            if (this.customerName.trim().length < 2) {
                this.errors.customer_name = ['Please enter your name.'];
            }

            const phone = this.customerPhone.replace(/[^0-9]/g, '');

            if (phone.length < 7) {
                this.errors.customer_phone = ['Enter a phone number we can call.'];
            }

            if (this.orderType === 'delivery' && this.address.trim().length < 6) {
                this.errors.delivery_address = ['Add a complete delivery address.'];
            }

            if (this.customerEmail.trim() !== '' && !/^\S+@\S+\.\S+$/.test(this.customerEmail.trim())) {
                this.errors.customer_email = ['That email address does not look right.'];
            }

            if (Object.keys(this.errors).length > 0) {
                this.formError = 'Please check the highlighted fields.';
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return false;
            }

            return true;
        },

        async submit() {
            if (this.submitting || !this.validate()) {
                return;
            }

            this.submitting = true;

            const token = document.querySelector('meta[name="csrf-token"]');
            const url = this.config.place_order_url || '/orders';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token ? token.content : '',
                    },
                    body: JSON.stringify({
                        order_type: this.orderType,
                        payment_method: this.paymentMethod,
                        customer_name: this.customerName.trim(),
                        customer_phone: this.customerPhone.trim(),
                        customer_email: this.customerEmail.trim(),
                        delivery_address: this.address.trim(),
                        notes: this.notes.trim(),
                        payment_reference: this.reference.trim(),
                        items: this.items.map((item) => ({
                            product_id: item.id,
                            qty: item.qty,
                            note: item.note,
                        })),
                    }),
                });

                const data = await response.json().catch(() => ({}));

                if (response.status === 422) {
                    this.errors = data.errors || {};
                    this.formError = data.message || 'Please check the highlighted fields.';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return;
                }

                if (!response.ok) {
                    this.formError = data.message || 'We could not place the order. Please try again.';
                    return;
                }

                try {
                    localStorage.removeItem(storageKey);
                } catch (error) {
                    // Ignore storage failures.
                }

                this.items = [];
                window.location.href = data.redirect;
            } catch (error) {
                this.formError = 'We could not reach the kitchen. Check your connection and try again.';
            } finally {
                this.submitting = false;
            }
        },

        money(cents) {
            return '₱' + (Number(cents || 0) / 100).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },
    };
};
