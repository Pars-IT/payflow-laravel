<?php

namespace Tests\Unit\Repositories;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Repositories\PaymentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private PaymentRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = app(PaymentRepository::class);
    }

    public function test_marks_payment_as_success(): void
    {
        $user = User::factory()->create();

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::Pending->value,
        ]);

        $this->repo->markSuccess($payment);

        $payment->refresh();

        $this->assertEquals(PaymentStatus::Success->value, $payment->status);
    }

    public function test_marks_payment_as_failed_with_reason(): void
    {
        $user = User::factory()->create();

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::Pending->value,
        ]);

        $this->repo->markFailed($payment, 'psp_error');

        $payment->refresh();

        $this->assertEquals(PaymentStatus::Failed->value, $payment->status);
        $this->assertEquals('psp_error', $payment->failure_reason);
    }

    public function test_does_not_override_already_successful_payment(): void
    {
        $user = User::factory()->create();

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::Success->value,
        ]);

        $this->repo->markFailed($payment, 'should_not_happen');

        $payment->refresh();

        $this->assertEquals(PaymentStatus::Success->value, $payment->status);
        $this->assertNull($payment->failure_reason);
    }

    public function test_does_not_override_already_failed_payment(): void
    {
        $user = User::factory()->create();

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'status' => PaymentStatus::Failed->value,
            'failure_reason' => 'initial_error',
        ]);

        $this->repo->markSuccess($payment);

        $payment->refresh();

        $this->assertEquals(PaymentStatus::Failed->value, $payment->status);
        $this->assertEquals('initial_error', $payment->failure_reason);
    }
}
