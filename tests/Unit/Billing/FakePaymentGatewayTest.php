<?php

namespace Tests\Unit;

use Carbon\Carbon;
use App\Concert;
use App\Billing\FakePaymentGateway;
use App\Billing\PaymentFailedException;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;


class FakePaymentGatewayTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    function charges_with_a_valid_payment_token_are_successful(){

        $paymentGateway = new FakePaymentGateway;

        $paymentGateway->charge(2500, $paymentGateway->getValidTestToken());

        $this->assertEquals(2500, $paymentGateway->totalCharges());

    }

    /** @test */
    public function charges_with_invalid_payment_token_fail(){

        try {
            $paymentGateway = new FakePaymentGateway;
            $paymentGateway->charge(2500, 'invalid-payment-token');
        } catch (PaymentFailedException $e) {
            return;
        }

        $this->fail();
    }

}
