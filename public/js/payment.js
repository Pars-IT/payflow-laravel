document.addEventListener('DOMContentLoaded', () => {

    const body = document.body;
    const toggleBtn = document.getElementById('toggle-theme');

    /* ---------------- THEME (localStorage) ---------------- */

    const THEME_KEY = 'theme'; // 'dark' | 'light'

    // Load saved theme
    const savedTheme = localStorage.getItem(THEME_KEY);

    if (savedTheme === 'dark') {
        body.classList.add('dark');
    }

    if (toggleBtn) {
        toggleBtn.innerText = body.classList.contains('dark')
            ? '☀️ Light mode'
            : '🌙 Dark mode';

        toggleBtn.onclick = () => {
            body.classList.toggle('dark');

            const isDark = body.classList.contains('dark');
            localStorage.setItem(THEME_KEY, isDark ? 'dark' : 'light');

            toggleBtn.innerText = isDark
                ? '☀️ Light mode'
                : '🌙 Dark mode';
        };
    }

    /* ---------------- PAYMENT FORM PAGE ---------------- */

    const form = document.getElementById('payment-form');

    if (form) {
        const spinner = document.getElementById('spinner');
        const statusEl = document.getElementById('status');
        const button = document.getElementById('pay-btn');

        async function poll(paymentId) {
            const interval = setInterval(async () => {
                const res = await fetch('/api/payments/' + paymentId);
                const payment = await res.json();

                if (payment.checkout_url) {
                    clearInterval(interval);
                    window.location.href = payment.checkout_url;
                    return;
                }

                statusEl.innerText = 'Status: ' + payment.status;
                statusEl.className = payment.status + ' text-center fw-bold';

                if (payment.status !== 'pending') {
                    spinner.style.display = 'none';
                    button.disabled = false;
                    clearInterval(interval);

                    if (payment.status === 'failed') {
                        statusEl.innerText =
                            'Failed: ' + (payment.failure_reason ?? 'unknown_error');
                    }
                }
            }, 1200);
        }

        form.onsubmit = async e => {
            e.preventDefault();

            button.disabled = true;
            spinner.style.display = 'block';

            statusEl.innerText = 'Status: pending';
            statusEl.className = 'pending text-center fw-bold';

            const res = await fetch('/api/payments', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    user_id: 1,
                    gateway: document.getElementById('gateway').value,
                    amount: document.getElementById('amount').value,
                    idempotency_key: 'web-' + Date.now()
                })
            });

            const data = await res.json();

            if (data.status === 'failed') {
                spinner.style.display = 'none';
                button.disabled = false;
                statusEl.innerText =
                    'Failed: ' + (data.failure_reason ?? 'unknown_error');
                statusEl.className = 'failed text-center fw-bold';
                return;
            }

            poll(data.id);
        };
    }

    /* ---------------- RETURN / STATUS PAGE ---------------- */

    if (window.PAYMENT_ID) {
        const el = document.getElementById('status');
        const payAgainEl = document.getElementById('pay-again-link');

        const poll = setInterval(async () => {
            const res = await fetch('/api/payments/' + window.PAYMENT_ID);
            const data = await res.json();

            el.innerText = data.status;
            el.className = data.status + ' fw-bold text-center';

            if (data.status !== 'pending') {
                clearInterval(poll);

                if (payAgainEl) payAgainEl.style.display = 'block';
            }
        }, 1500);
    }

});
