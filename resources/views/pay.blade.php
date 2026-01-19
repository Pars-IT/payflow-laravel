<!DOCTYPE html>
<html>
<head>
    <title>Test Payment</title>
    <style>
        :root {
            --bg: #ffffff;
            --text: #111827;
            --card: #f9fafb;
            --border: #e5e7eb;

            --pending: #f59e0b;
            --success: #16a34a;
            --failed: #dc2626;
        }

        body.dark {
            --bg: #0f172a;
            --text: #e5e7eb;
            --card: #020617;
            --border: #1e293b;
        }

        body {
            font-family: Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            transition: background 0.2s, color 0.2s;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            padding: 20px;
            width: 320px;
            border-radius: 8px;
        }

        button {
            padding: 6px 12px;
            cursor: pointer;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text);
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        #spinner {
            display: none;
            margin-top: 10px;
            opacity: 0.8;
        }

        #status {
            margin-top: 10px;
            font-weight: bold;
        }

        .pending { color: var(--pending); }
        .success { color: var(--success); }
        .failed  { color: var(--failed); }

        .theme-toggle {
            margin-bottom: 10px;
            font-size: 14px;
        }

        input {
            padding: 6px;
            width: 100%;
            margin-top: 4px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text);
        }
    </style>
</head>
<body>

<div class="card">
    <div class="theme-toggle">
        <button id="toggle-theme">🌙 Dark mode</button>
    </div>

    <h3>Test Payment</h3>

    <form id="payment-form">

        <label>
            Gateway:
            <select id="gateway">
                <option value="ideal">iDEAL</option>
                <option value="ing">ING</option>
                <option value="abn-amro">ABN AMRO</option>
            </select>
        </label>
        <br><br>

        <label>
            Amount (cents):
            <input type="number" id="amount" value="1500" min="1" required>
        </label>
        <br><br>
        <button id="pay-btn">Pay</button>
    </form>

    <div id="spinner">⏳ Processing payment...</div>
    <p id="status"></p>
</div>

<script>
const form = document.getElementById('payment-form');
const spinner = document.getElementById('spinner');
const statusEl = document.getElementById('status');
const button = document.getElementById('pay-btn');
const toggleBtn = document.getElementById('toggle-theme');
const body = document.body;

/* -------- Theme toggle -------- */
toggleBtn.onclick = () => {
    body.classList.toggle('dark');
    toggleBtn.innerText = body.classList.contains('dark')
        ? '☀️ Light mode'
        : '🌙 Dark mode';
};

const hour = new Date().getHours();

if (hour >= 18 || hour < 8) {
    body.classList.add('dark');
    toggleBtn.innerText = '☀️ Light mode';
} else {
    body.classList.remove('dark');
    toggleBtn.innerText = '🌙 Dark mode';
}

/* -------- Polling -------- */
async function poll(paymentId) {
    const interval = setInterval(async () => {
        const res = await fetch('/api/payments/' + paymentId);
        const payment = await res.json();

        statusEl.innerText = 'Status: ' + payment.status;
        statusEl.className = payment.status;

        if (payment.status !== 'pending') {
            spinner.style.display = 'none';
            button.disabled = false;
            clearInterval(interval);
        }
    }, 1500);
}

/* -------- Submit -------- */
form.onsubmit = async e => {
    e.preventDefault();

    button.disabled = true;
    spinner.style.display = 'block';

    statusEl.innerText = 'Status: pending';
    statusEl.className = 'pending';

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

    const payment = await res.json();
    poll(payment.id);
};
</script>

</body>
</html>
