<p>Hello {{ $payment->user->name }}</p>

<p>
    Your payment of €{{ number_format($payment->amount / 100, 2) }} was <b>{{ $status }}</b>.
</p>

@if($status === 'failed')
    <p>Reason: {{ $reason }}</p>
@endif


<p>Payment ID: {{ $payment->id }}</p>
<p>Gateway: <b>{{ $payment->gateway }}</b></p>
