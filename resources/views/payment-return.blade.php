<!DOCTYPE html>
<html>
<head>
    <title>Payment status</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #0f172a;
            color: #e5e7eb;
        }

        .card {
            max-width: 400px;
            margin: 80px auto;
            background: #020617;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .pending { color: #f59e0b; }
        .success { color: #16a34a; }
        .failed  { color: #dc2626; }
    </style>
</head>
<body>

<div class="card">
    <h3>Payment status</h3>
    <p id="status" class="pending">Processing...</p>
</div>

<script>
const paymentId = "{{ $payment->id }}";

const poll = setInterval(async () => {
    const res = await fetch('/api/payments/' + paymentId);
    const data = await res.json();

    const el = document.getElementById('status');
    el.innerText = data.status;
    el.className = data.status;

    if (data.status !== 'pending') {
        clearInterval(poll);
    }
}, 1500);
</script>

</body>
</html>
