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

// Dialogs (the Browse filters drawer on small screens).
document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-dialog-open]');
    if (opener) document.getElementById(opener.dataset.dialogOpen)?.showModal();

    if (event.target.closest('[data-dialog-close]')) event.target.closest('dialog')?.close();

    // Clicking the backdrop closes the dialog.
    if (event.target instanceof HTMLDialogElement) event.target.close();
});

// Search lightbox: ⌘K / Ctrl K or the header button opens it. Livewire (SearchPanel) loads the results.
const searchDialog = document.getElementById('search-dialog');

if (searchDialog) {
    const openSearch = () => {
        if (searchDialog.open) return;
        searchDialog.showModal();
        searchDialog.querySelector('[data-search-input]')?.select();
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
}

// The status toast fades out after 3 seconds. One comes with the page after a form; Livewire
// components send more with a "toast" event (following, favourites).
const hideToast = (toast) => {
    setTimeout(() => {
        toast.toggleAttribute('data-hidden', true);
        toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    }, 3000);
};

document.querySelectorAll('[data-toast]').forEach(hideToast);

window.addEventListener('toast', (event) => {
    const template = document.getElementById('toast-template');
    if (!template || !event.detail?.message) return;
    document.querySelectorAll('[data-toast]').forEach((old) => old.remove());
    const toast = template.content.firstElementChild.cloneNode(true);
    toast.textContent = event.detail.message;
    // Inside an open dialog the toast has to be in the dialog to show above its backdrop.
    (document.querySelector('dialog[open]') ?? document.body).append(toast);
    hideToast(toast);
});

// Menus (the avatar menu in the header) are <details>: a click outside or Escape closes them.
document.addEventListener('click', (event) => {
    document.querySelectorAll('details[data-menu][open]').forEach((menu) => {
        if (!menu.contains(event.target)) menu.open = false;
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('details[data-menu][open]').forEach((menu) => {
        menu.open = false;
        menu.querySelector('summary')?.focus();
    });
});

// The header gets its glass strip once the page is scrolled.
const header = document.querySelector('[data-header]');

if (header) {
    const update = () => header.toggleAttribute('data-scrolled', window.scrollY > 8);
    update();
    window.addEventListener('scroll', update, { passive: true });
}
