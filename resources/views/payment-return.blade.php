<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Status</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="{{ asset('css/payment.css') }}" rel="stylesheet">
</head>
<body>

<div class="container">
    <div class="card mx-auto mt-5 p-4 shadow-sm" style="max-width:420px">

        <!-- Theme toggle -->
        <div class="d-flex justify-content-end mb-2">
            <button id="toggle-theme" class="btn btn-sm btn-outline-secondary">
                🌙 Dark mode
            </button>
        </div>

        <h5 class="text-center mb-3">Payment Status</h5>

        <p id="status" class="pending fw-bold text-center">
            Processing...
        </p>

        <div id="pay-again-link" class="text-center mt-3" style="display:none;">
            <a href="{{ url('/pay') }}" class="btn btn-sm btn-outline-primary">Pay Again</a>
        </div>

    </div>
</div>

<script>
    window.PAYMENT_ID = "{{ $payment->id }}";
</script>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="{{ asset('js/payment.js') }}"></script>
</body>
</html>
