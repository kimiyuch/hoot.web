// Tell the server which timezone this browser is in, so release times are shown in local time.
// The first visit (or after travelling) reloads once with the right timezone.
const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

if (timezone && document.documentElement.dataset.timezone !== timezone) {
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
