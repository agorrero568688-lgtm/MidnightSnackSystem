/**
 * POS till state. Rendered as an Alpine component on the Livewire terminal page.
 *
 * The cart is intentionally client-side so scanning and quantity changes are
 * instant. The server re-prices every line at checkout, so prices shown here
 * are display-only.
 */
window.posTerminal = function () {
    return {
        catalog: [],
        categories: [],
        shift: null,
        config: {},
        cart: [],
        search: '',
        activeCategory: 'all',
        scan: '',
        paymentMethod: 'cash',
        tendered: '',
        discountType: 'none',
        customerName: '',
        discountId: '',
        showDiscount: false,
        busy: false,
        error: '',
        success: null,
        parked: [],
        now: '',
        showCloseShift: false,
        countedCash: '',
        openingCash: '',
        shiftBusy: false,
        shiftError: '',
        ready: false,

        init() {
            const data = JSON.parse(document.getElementById('pos-bootstrap').textContent);
            this.catalog = data.catalog || [];
            this.categories = data.categories || [];
            this.shift = data.shift || null;
            this.config = data.config || {};
            this.parked = this.loadParked();

            this.tick();
            setInterval(() => this.tick(), 1000);

            window.addEventListener('keydown', (event) => {
                if (event.key === 'F2') {
                    event.preventDefault();
                    this.focusScan();
                }
                if (event.key === 'F4') {
                    event.preventDefault();
                    this.$refs.tendered?.focus();
                }
                if (event.key === 'Escape' && !this.success) {
                    this.error = '';
                }
            });

            this.ready = true;
        },

        /* ---------------------------------------------------------------- */
        /* Derived state                                                    */
        /* ---------------------------------------------------------------- */

        get hasShift() {
            return this.shift !== null;
        },

        get filteredProducts() {
            const term = this.search.trim().toLowerCase();

            return this.catalog.filter((product) => {
                if (this.activeCategory !== 'all' && String(product.category_id) !== String(this.activeCategory)) {
                    return false;
                }
                if (!term) {
                    return true;
                }

                return (
                    product.name.toLowerCase().includes(term) ||
                    (product.sku || '').toLowerCase().includes(term) ||
                    (product.barcode || '').includes(term)
                );
            });
        },

        get cartCount() {
            return this.cart.reduce((sum, item) => sum + item.qty, 0);
        },

        get subtotalCents() {
            return this.cart.reduce((sum, item) => sum + item.price_cents * item.qty, 0);
        },

        get discountCents() {
            if (this.discountType === 'none') {
                return 0;
            }

            return Math.round((this.subtotalCents * 20) / 100);
        },

        get totalCents() {
            return Math.max(0, this.subtotalCents - this.discountCents);
        },

        get tenderedCents() {
            return this.toCents(this.tendered);
        },

        get changeCents() {
            return Math.max(0, this.tenderedCents - this.totalCents);
        },

        get canPay() {
            if (this.cart.length === 0 || this.busy || !this.hasShift) {
                return false;
            }

            if (this.discountType !== 'none' && (!this.customerName.trim() || !this.discountId.trim())) {
                return false;
            }

            if (this.paymentMethod === 'cash' && this.tenderedCents < this.totalCents) {
                return false;
            }

            return true;
        },

        /* ---------------------------------------------------------------- */
        /* Formatting                                                       */
        /* ---------------------------------------------------------------- */

        money(cents) {
            return (
                '₱' +
                (Math.round(cents) / 100).toLocaleString('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                })
            );
        },

        toCents(value) {
            const number = parseFloat(String(value ?? '').replace(/,/g, ''));
            return Number.isFinite(number) ? Math.round(number * 100) : 0;
        },

        tick() {
            this.now = new Date().toLocaleTimeString('en-PH', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        },

        inCart(productId) {
            return this.cart.find((row) => row.id === productId)?.qty ?? 0;
        },

        isSoldOut(product) {
            return !product.is_available;
        },

        /* ---------------------------------------------------------------- */
        /* Cart                                                             */
        /* ---------------------------------------------------------------- */

        addProduct(product) {
            if (this.isSoldOut(product)) {
                this.flashError(`"${product.name}" is marked as sold out.`);
                return;
            }

            const existing = this.cart.find((row) => row.id === product.id);

            if (existing) {
                existing.qty++;
            } else {
                this.cart.push({
                    id: product.id,
                    name: product.name,
                    sku: product.sku,
                    price_cents: product.price_cents,
                    qty: 1,
                });
            }

            this.error = '';
        },

        increment(item) {
            item.qty++;
        },

        decrement(item) {
            item.qty--;

            if (item.qty <= 0) {
                this.removeItem(item);
            }
        },

        removeItem(item) {
            this.cart = this.cart.filter((row) => row !== item);
        },

        clearCart() {
            if (this.cart.length === 0) {
                return;
            }

            if (!confirm('Clear the current cart?')) {
                return;
            }

            this.resetCart();
        },

        resetCart() {
            this.cart = [];
            this.tendered = '';
            this.discountType = 'none';
            this.customerName = '';
            this.discountId = '';
            this.showDiscount = false;
            this.paymentMethod = 'cash';
            this.error = '';
        },

        /* ---------------------------------------------------------------- */
        /* Scanner                                                          */
        /* ---------------------------------------------------------------- */

        focusScan() {
            this.$nextTick(() => this.$refs.scan?.focus());
        },

        handleScan() {
            const code = this.scan.trim();

            if (code === '') {
                return;
            }

            this.scan = '';

            const product =
                this.catalog.find((row) => row.barcode === code) ||
                this.catalog.find((row) => (row.sku || '').toLowerCase() === code.toLowerCase());

            if (product) {
                this.addProduct(product);
                return;
            }

            const visible = this.filteredProducts;

            if (visible.length === 1) {
                this.addProduct(visible[0]);
                return;
            }

            this.flashError(`No product matches "${code}".`);
            this.beep();
        },

        beep() {
            try {
                const context = new (window.AudioContext || window.webkitAudioContext)();
                const osc = context.createOscillator();
                const gain = context.createGain();

                osc.connect(gain);
                gain.connect(context.destination);
                osc.frequency.value = 220;
                gain.gain.value = 0.05;
                osc.start();
                osc.stop(context.currentTime + 0.12);
            } catch (ignored) {
                /* audio is a nice-to-have */
            }
        },

        flashError(message) {
            this.error = message;
            setTimeout(() => {
                if (this.error === message) {
                    this.error = '';
                }
            }, 4000);
        },

        /* ---------------------------------------------------------------- */
        /* Payment                                                          */
        /* ---------------------------------------------------------------- */

        setTendered(cents) {
            this.tendered = (cents / 100).toFixed(2);
        },

        tenderExact() {
            this.setTendered(this.totalCents);
        },

        async pay() {
            if (!this.canPay) {
                return;
            }

            this.busy = true;
            this.error = '';

            try {
                const response = await this.$wire.checkout({
                    items: this.cart.map((item) => ({ product_id: item.id, qty: item.qty })),
                    payment_method: this.paymentMethod,
                    amount_tendered: this.paymentMethod === 'cash' ? this.tendered : null,
                    discount_type: this.discountType === 'none' ? 'none' : this.discountType,
                    customer_name: this.customerName || null,
                    discount_id_number: this.discountId || null,
                });

                if (!response.ok) {
                    this.flashError(response.message || 'Could not complete the sale.');
                    return;
                }

                this.success = response.sale;
                this.resetCart();
            } catch (error) {
                this.flashError('Checkout failed. Please try again.');
            } finally {
                this.busy = false;
            }
        },

        newSale() {
            this.success = null;
            this.focusScan();
        },

        printReceipt() {
            if (!this.success) {
                return;
            }

            window.open(this.success.receipt_url, '_blank', 'width=420,height=680');
        },

        /* ---------------------------------------------------------------- */
        /* Parked sales                                                     */
        /* ---------------------------------------------------------------- */

        loadParked() {
            try {
                return JSON.parse(localStorage.getItem('dms.parked') || '[]');
            } catch (ignored) {
                return [];
            }
        },

        saveParked() {
            localStorage.setItem('dms.parked', JSON.stringify(this.parked));
        },

        parkSale() {
            if (this.cart.length === 0) {
                return;
            }

            this.parked.unshift({
                id: Date.now(),
                at: new Date().toISOString(),
                cart: this.cart,
                discountType: this.discountType,
                customerName: this.customerName,
                discountId: this.discountId,
            });
            this.saveParked();
            this.resetCart();
        },

        resumeSale(index) {
            const parked = this.parked.splice(index, 1)[0];

            if (!parked) {
                return;
            }

            this.saveParked();
            this.cart = parked.cart;
            this.discountType = parked.discountType;
            this.customerName = parked.customerName;
            this.discountId = parked.discountId;
        },

        discardParked(index) {
            this.parked.splice(index, 1);
            this.saveParked();
        },

        /* ---------------------------------------------------------------- */
        /* Shift                                                            */
        /* ---------------------------------------------------------------- */

        async openShift() {
            this.shiftBusy = true;
            this.shiftError = '';

            try {
                const response = await this.$wire.openShift(this.openingCash || '0');

                if (!response.ok) {
                    this.shiftError = response.message;
                    return;
                }

                this.shift = response.shift;
                this.openingCash = '';
                this.focusScan();
            } finally {
                this.shiftBusy = false;
            }
        },

        async closeShift() {
            this.shiftBusy = true;
            this.shiftError = '';

            try {
                const response = await this.$wire.closeShift(this.countedCash || '0');

                if (!response.ok) {
                    this.shiftError = response.message;
                    return;
                }

                this.shift = null;
                this.showCloseShift = false;
                this.countedCash = '';
                this.resetCart();
            } finally {
                this.shiftBusy = false;
            }
        },
    };
};
