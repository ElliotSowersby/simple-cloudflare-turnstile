function turnstileComplyChanged(event) {
    const comply = event.target.checked;

    if (!comply)
        document.cookie = 'cfturnstile_compliance=revoked; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    else {
        const expiryDate = new Date();
        expiryDate.setFullYear(expiryDate.getFullYear() + 1);
        document.cookie = 'cfturnstile_compliance=granted; expires=' + expiryDate.toUTCString() + '; path=/;';
    }

    document.location.reload(true)
}