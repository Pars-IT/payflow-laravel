/* ---------- THEME ---------- */
const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

function applySystemTheme(isDark) {
    document.body.classList.toggle('dark', isDark);
}

// Apply theme immediately
applySystemTheme(mediaQuery.matches);

// Listen for system theme changes
mediaQuery.addEventListener('change', (event) => {
    applySystemTheme(event.matches);
});

document.addEventListener('DOMContentLoaded', () => {
    /* ---------- CREATE PAYMENT ---------- */
    const form = document.getElementById('payment-form');

    if (form) {
        const spinner = document.getElementById('spinner');
        const button = document.getElementById('pay-btn');

        form.onsubmit = async e => {
            e.preventDefault();

            button.disabled = true;
            spinner.style.display = 'block';

            const res = await fetch('/api/payments', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id: 1,
                    gateway: document.getElementById('gateway').value,
                    amount: document.getElementById('amount').value,
                    idempotency_key: 'web-' + Date.now()
                })
            });

            const data = await res.json();

            // Redirect to resumable status page
            window.location.href = `/payments/${data.id}`;
        };
    }

    /* ---------- STATUS PAGE (POLLING) ---------- */
    if (window.PAYMENT_ID) {
        const statusEl = document.getElementById('status');

        const poll = setInterval(async () => {
            const res = await fetch('/api/payments/' + window.PAYMENT_ID);
            const payment = await res.json();

            // Redirect to PSP
            if (payment.checkout_url && payment.status === 'pending') {
                statusEl.innerText = 'Redirecting to bank...';
                statusEl.className = 'pending fw-bold text-center';

                clearInterval(poll);
                window.location.href = payment.checkout_url;
                return;
            }

            // Pending but no redirect yet
            if (payment.status === 'pending') {
                statusEl.innerText = 'Processing payment...';
                statusEl.className = 'pending fw-bold text-center';
                return;
            }

            // Final states
            clearInterval(poll);

            if (payment.status === 'success') {
                statusEl.innerText = 'Payment successful';
                statusEl.className = 'success fw-bold text-center';
            }

            if (payment.status === 'failed') {
                statusEl.innerText =
                    'Payment failed: ' + (payment.failure_reason ?? 'unknown_error');
                statusEl.className = 'failed fw-bold text-center';
            }

            document.getElementById('pay-new-link').style.display = 'block';
        }, 1500);
    }

    /* ---------- LOAD USER CREDIT ---------- */
    const creditEl = document.getElementById('user-credit');

    if (creditEl) {
        fetch('/api/wallets/1/credit')
            .then(res => res.json())
            .then(data => {
                const euro = (data.balance / 100).toFixed(2);
                creditEl.innerText = 'Your credit: €' + euro;
            })
            .catch(() => {
                creditEl.innerText = 'Error retrieving credit';
            });
    }

});
