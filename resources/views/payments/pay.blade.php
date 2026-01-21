<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Payment</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="{{ asset('css/payment.css') }}" rel="stylesheet">
</head>
<body>

<div class="container">
    <div class="card mx-auto mt-5 p-4 shadow-sm" style="max-width:420px">

        <div class="d-flex justify-content-end mb-2">
            <button id="toggle-theme" class="btn btn-sm btn-outline-secondary">
                🌙 Dark mode
            </button>
        </div>

        <h5 class="text-center mb-4">Test Payment</h5>

        <form id="payment-form">

            <div class="mb-3">
                <label class="form-label">Gateway</label>
                <select id="gateway" class="form-select">
                    <option value="ideal">iDEAL</option>
                    <option value="mollie">Mollie</option>
                    <option value="ing">ING</option>
                    <option value="abn-amro">ABN AMRO</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Amount (cents)</label>
                <input
                    type="number"
                    id="amount"
                    class="form-control"
                    value="1500"
                    min="1"
                    required
                >
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/payment.js') }}"></script>
</body>
</html>
