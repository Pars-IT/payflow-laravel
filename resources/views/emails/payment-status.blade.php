<p>Hello {{ $name }}</p>

<p>Your payment of {{ $payment->amount }} cents was <b>{{ $status }}</b>.</p>

@if($status === 'failed')
    <p>Reason: {{ $reason }}</p>
@endif


<p>Payment ID: {{ $payment->id }}</p>
