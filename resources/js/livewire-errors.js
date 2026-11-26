/**
 * Livewire v4 shows a raw HTML dialog when an update request doesn't come back
 * as JSON. A slow Settings save can finish on the server but return an HTML
 * error page, which makes Livewire dump the whole page (including the
 * "Store settings saved." flash banner) into a black iframe.
 *
 * Turn that specific failure into a normal reload so the regular styled flash
 * message is shown. Other Livewire requests keep their default behaviour.
 */
const SAVED_MESSAGE = 'Store settings saved.';
const SAVED_FLAG = 'midnightsnack:settings-saved';

function rememberSaved()
{
    try {
        sessionStorage.setItem(SAVED_FLAG, '1');
    } catch (error) {
        // Storage can be unavailable in private browsing; the flash is enough.
    }
}

function consumeSaved()
{
    try {
        if (sessionStorage.getItem(SAVED_FLAG) !== '1') {
            return false;
        }

        sessionStorage.removeItem(SAVED_FLAG);

        return true;
    } catch (error) {
        return false;
    }
}

function showSavedBanner()
{
    let banner = document.getElementById('flash-status');

    if (! banner) {
        const container = document.querySelector('main') || document.body;

        banner = document.createElement('div');
        banner.id = 'flash-status';
        banner.className = 'mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800';
        banner.setAttribute('role', 'status');
        container.prepend(banner);
    }

    banner.textContent = SAVED_MESSAGE;
}

function dismissLivewireDialog()
{
    const modal = document.getElementById('livewire-error');

    if (! modal) {
        return;
    }

    modal.remove();
    document.body.style.overflow = '';
}

function handleRequestError({ content, preventDefault })
{
    const body = typeof content === 'string' ? content : '';

    if (! body.includes(SAVED_MESSAGE)) {
        return;
    }

    if (typeof preventDefault === 'function') {
        preventDefault();
    }

    dismissLivewireDialog();
    rememberSaved();
    window.location.reload();
}

function register()
{
    if (! window.Livewire || typeof window.Livewire.hook !== 'function') {
        return false;
    }

    window.Livewire.hook('request', ({ fail }) => {
        if (typeof fail === 'function') {
            fail(handleRequestError);
        }
    });

    return true;
}

if (! register()) {
    document.addEventListener('livewire:init', register, { once: true });
}

function onReady(callback)
{
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback, { once: true });
    } else {
        callback();
    }
}

onReady(() => {
    if (consumeSaved()) {
        showSavedBanner();
    }
});
