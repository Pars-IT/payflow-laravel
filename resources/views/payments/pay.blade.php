<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Test Payment</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/gh/twbs/bootstrap@v5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="{{ asset('css/payment.css') }}?v={{ filemtime(public_path('css/payment.css')) }}" rel="stylesheet">
</head>

<body>

    <div class="container">
        <div class="card mx-auto mt-5 p-4 shadow-sm" style="max-width:420px">

            <h5 class="text-center mb-4 text-primary">Test Payment with AWS v2.1</h5>

            <h6 id="user-credit" class="text-center mb-3 text-info">
                Your credit: ...
            </h6>

            <form id="payment-form">

                <div class="mb-3">
                    <label class="form-label">Gateway</label>
                    <select id="gateway" class="form-select"></select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">€</span>
                        <input type="number" id="amount" class="form-control" value="1.00" min="0.01"
                            step="0.01" required>
                    </div>
                </div>

                <button id="pay-btn" class="btn btn-primary w-100">
                    Pay
                </button>
            </form>

            <div id="spinner" class="text-center mt-3">
                ⏳ Processing payment...
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/gh/twbs/bootstrap@v5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/payment.js') }}?v={{ filemtime(public_path('js/payment.js')) }}"></script>
</body>

</html>
