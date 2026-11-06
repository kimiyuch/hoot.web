// Tell the server which timezone this browser is in, so release times are shown in local time.
// The first visit (or after travelling) reloads once with the right timezone. Only app pages say
// which timezone they used; the landing and login pages don't show times.
const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
const pageTimezone = document.documentElement.dataset.timezone;

if (timezone && pageTimezone && pageTimezone !== timezone) {
    document.cookie = `tz=${encodeURIComponent(timezone)}; path=/; max-age=31536000; samesite=lax`;
    if (!document.cookie.includes('tz-reloaded=1')) {
        document.cookie = 'tz-reloaded=1; path=/; max-age=60; samesite=lax';
        location.reload();
    }
}

// Copy buttons (the calendar feed URL).
document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy]');
    if (!button) return;
    await navigator.clipboard.writeText(button.dataset.copy);
    const label = button.textContent;
    button.textContent = 'Copied';
    setTimeout(() => (button.textContent = label), 1500);
});

// Filter dropdowns (Browse) apply as soon as they change.
document.addEventListener('change', (event) => {
    if (event.target.matches('[data-autosubmit]')) event.target.form.requestSubmit();
});

// Dialogs (the Browse filters drawer on small screens).
document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-dialog-open]');
    if (opener) document.getElementById(opener.dataset.dialogOpen)?.showModal();

    if (event.target.closest('[data-dialog-close]')) event.target.closest('dialog')?.close();

    // Clicking the backdrop closes the dialog.
    if (event.target instanceof HTMLDialogElement) event.target.close();
});

// Search lightbox: ⌘K / Ctrl K or the header button opens it; results load as you type.
const searchDialog = document.getElementById('search-dialog');

if (searchDialog) {
    const input = searchDialog.querySelector('[data-search-input]');
    const results = searchDialog.querySelector('[data-search-results]');
    const hint = results.innerHTML;
    let timer;
    let request;

    const openSearch = () => {
        if (searchDialog.open) return;
        searchDialog.showModal();
        input.select();
    };

    // Show the shortcut for this platform.
    if (!/Mac|iPhone|iPad/.test(navigator.platform)) {
        document.querySelectorAll('[data-shortcut]').forEach((kbd) => (kbd.textContent = 'Ctrl K'));
    }

    document.addEventListener('keydown', (event) => {
        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            searchDialog.open ? searchDialog.close() : openSearch();
        }
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-search-open]')) return;
        event.preventDefault();
        openSearch();
    });

    input.addEventListener('input', () => {
        clearTimeout(timer);
        request?.abort();
        const query = input.value.trim();

        if (query.length < 2) {
            results.innerHTML = hint;
            return;
        }

        timer = setTimeout(async () => {
            request = new AbortController();
            results.setAttribute('aria-busy', 'true');
            try {
                const response = await fetch(`/search?q=${encodeURIComponent(query)}`, {
                    headers: { 'X-Possum-Partial': '1' },
                    signal: request.signal,
                });
                results.innerHTML = await response.text();
            } catch (error) {
                if (error.name !== 'AbortError') results.innerHTML = '<p class="px-3 py-6 text-center text-sm/6 text-white/45">Search isn’t available right now.</p>';
            } finally {
                results.removeAttribute('aria-busy');
            }
        }, 250);
    });
}

// The status toast fades out after 3 seconds.
const toast = document.querySelector('[data-toast]');

if (toast) {
    setTimeout(() => {
        toast.toggleAttribute('data-hidden', true);
        toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    }, 3000);
}

// The header gets its glass strip once the page is scrolled.
const header = document.querySelector('[data-header]');

if (header) {
    const update = () => header.toggleAttribute('data-scrolled', window.scrollY > 8);
    update();
    window.addEventListener('scroll', update, { passive: true });
}
