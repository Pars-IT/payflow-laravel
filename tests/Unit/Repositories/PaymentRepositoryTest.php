<?php

namespace Tests\Unit\Repositories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private PaymentRepositoryInterface $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = app(PaymentRepositoryInterface::class);
    }

    public function test_it_marks_pending_payment_as_success(): void
    {
        $payment = $this->createPayment(PaymentStatus::Pending);

        // act
        $result = $this->repo->markSuccess($payment->id);

        // assert
        $payment->refresh();

        $this->assertTrue($result);
        $this->assertSame(PaymentStatus::Success->value, $payment->status);
    }

    public function test_it_marks_pending_payment_as_failed_with_reason(): void
    {
        $payment = $this->createPayment(PaymentStatus::Pending);

        // act
        $result = $this->repo->markFailed($payment->id, 'psp_error');

        // assert
        $payment->refresh();

        $this->assertTrue($result);
        $this->assertSame(PaymentStatus::Failed->value, $payment->status);
        $this->assertSame('psp_error', $payment->failure_reason);
    }

    public function test_it_does_not_override_already_successful_payment(): void
    {
        $payment = $this->createPayment(PaymentStatus::Success);

        // act
        $result = $this->repo->markFailed($payment->id, 'should_not_happen');

        // assert
        $payment->refresh();

        $this->assertFalse($result);
        $this->assertSame(PaymentStatus::Success->value, $payment->status);
        $this->assertNull($payment->failure_reason);
    }

    public function test_it_does_not_override_already_failed_payment(): void
    {
        $payment = $this->createPayment(
            PaymentStatus::Failed,
            ['failure_reason' => 'initial_error']
        );

        // act
        $result = $this->repo->markSuccess($payment->id);

        // assert
        $payment->refresh();

        $this->assertFalse($result);
        $this->assertSame(PaymentStatus::Failed->value, $payment->status);
        $this->assertSame('initial_error', $payment->failure_reason);
    }

    private function createPayment(
        PaymentStatus $status,
        array $overrides = []
    ): Payment {
        $user = User::factory()->create();

        return Payment::factory()->create(array_merge([
            'user_id' => $user->id,
            'status' => $status->value,
        ], $overrides));
    }

    public function test_it_finds_payment_by_id(): void
    {
        $payment = Payment::factory()->create();

        $found = $this->repo->findById($payment->id);

        $this->assertSame($payment->id, $found->id);
    }

    public function test_it_finds_payment_by_idempotency_key(): void
    {
        $payment = Payment::factory()->create([
            'idempotency_key' => 'key-123',
        ]);

        $found = $this->repo->findByIdempotencyKey('key-123');

        $this->assertNotNull($found);
        $this->assertSame($payment->id, $found->id);
    }

    public function test_it_returns_null_when_idempotency_key_not_found(): void
    {
        $result = $this->repo->findByIdempotencyKey('missing-key');

        $this->assertNull($result);
    }

    public function test_it_finds_payment_by_provider_payment_id(): void
    {
        $payment = Payment::factory()->create([
            'provider' => 'mollie',
            'provider_payment_id' => 'tr_123',
        ]);

        $found = $this->repo->findByProviderPaymentId('mollie', 'tr_123');

        $this->assertSame($payment->id, $found->id);
    }

    public function test_it_creates_pending_payment(): void
    {
        $payment = $this->repo->createPending([
            'user_id' => User::factory()->create()->id,
            'gateway' => 'ideal',
            'amount' => 2000,
            'idempotency_key' => 'key-xyz',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Pending->value,
            'idempotency_key' => 'key-xyz',
        ]);
    }

    public function test_it_attaches_provider_data_to_payment(): void
    {
        $payment = Payment::factory()->create();

        $this->repo->attachProviderData(
            $payment->id,
            'mollie',
            'tr_999',
            'https://checkout.test'
        );

        $payment->refresh();

        $this->assertSame('mollie', $payment->provider);
        $this->assertSame('tr_999', $payment->provider_payment_id);
        $this->assertSame('https://checkout.test', $payment->provider_checkout_url);
    }

    public function test_it_marks_pending_payment_as_timed_out(): void
    {
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::Pending->value,
        ]);

        $this->repo->markTimedOut($payment);

        $payment->refresh();

        $this->assertSame(PaymentStatus::Failed->value, $payment->status);
        $this->assertSame('processing_timeout', $payment->failure_reason);
    }

    public function test_it_does_not_timeout_non_pending_payment(): void
    {
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::Success->value,
        ]);

        $result = $this->repo->markTimedOut($payment);

        $this->assertFalse($result);
    }
}
