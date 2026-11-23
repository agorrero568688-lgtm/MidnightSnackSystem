/**
 * Staff order board: in-app sound and browser notifications.
 */
window.orderBoard = function () {
    return {
        sound: true,
        notifications: false,

        init() {
            try {
                this.sound = localStorage.getItem('dms.board.sound') !== '0';
            } catch (error) {
                this.sound = true;
            }

            if ('Notification' in window) {
                this.notifications = Notification.permission === 'granted';
            }
        },

        toggleSound() {
            this.sound = !this.sound;

            try {
                localStorage.setItem('dms.board.sound', this.sound ? '1' : '0');
            } catch (error) {
                // Ignore storage failures.
            }

            if (this.sound) {
                this.beep();
            }
        },

        async enableNotifications() {
            if (!('Notification' in window)) {
                alert('This browser does not support notifications.');
                return;
            }

            const permission = await Notification.requestPermission();
            this.notifications = permission === 'granted';

            if (this.notifications) {
                new Notification('Order alerts enabled', {
                    body: 'You will be notified when a new online order arrives.',
                });
            }
        },

        testAlert() {
            this.beep();

            if (this.notifications) {
                new Notification('Test order alert', {
                    body: 'This is what a new order will look like.',
                });
            }
        },

        beep() {
            try {
                const Context = window.AudioContext || window.webkitAudioContext;

                if (!Context) {
                    return;
                }

                this.audioContext = this.audioContext || new Context();

                const context = this.audioContext;

                if (context.state === 'suspended') {
                    context.resume();
                }

                const start = context.currentTime;

                [0, 0.18, 0.36].forEach((offset, index) => {
                    const oscillator = context.createOscillator();
                    const gain = context.createGain();

                    oscillator.type = 'sine';
                    oscillator.frequency.value = index === 2 ? 1046 : 880;

                    gain.gain.setValueAtTime(0.0001, start + offset);
                    gain.gain.exponentialRampToValueAtTime(0.3, start + offset + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.0001, start + offset + 0.15);

                    oscillator.connect(gain);
                    gain.connect(context.destination);
                    oscillator.start(start + offset);
                    oscillator.stop(start + offset + 0.16);
                });
            } catch (error) {
                // Sound is a nice-to-have; the board still works without it.
            }
        },

        onOrderAlert(detail) {
            if (this.sound) {
                this.beep();
            }

            if (
                this.notifications
                && 'Notification' in window
                && Notification.permission === 'granted'
            ) {
                const reference = detail && detail.reference ? detail.reference : '';

                new Notification('New online order', {
                    body: reference
                        ? reference + ' is waiting on the board.'
                        : 'A new order just came in.',
                    tag: 'dms-order-' + reference,
                });
            }
        },
    };
};
