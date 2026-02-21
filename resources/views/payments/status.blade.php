<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment Status</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/payment.css') }}" rel="stylesheet">
</head>

<body>

    <div class="container">
        <div class="card mx-auto mt-5 p-4 shadow-sm" style="max-width:420px">

            <h5 class="text-center mb-3 text-primary">Payment Status</h5>

            <p id="status" class="pending fw-bold text-center">
                Loading payment status...
            </p>

            <div id="pay-new-link" class="text-center mt-3" style="display:none;">
                <a href="/pay" class="btn btn-sm btn-outline-primary">
                    New Payment
                </a>
            </div>
        </div>
    </div>

    <script>
        window.PAYMENT_ID = "{{ $paymentId }}";
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/payment.js') }}"></script>
</body>

</html>
