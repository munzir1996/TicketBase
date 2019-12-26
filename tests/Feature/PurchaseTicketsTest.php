<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use App\Concert;
use App\Billing\FakePaymentGateway;
use App\Billing\PaymentGateway;
use Carbon\Carbon;

class PurchaseTicketsTest extends TestCase
{

    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentGateway = new FakePaymentGateway();
        $this->app->instance(PaymentGateway::class, $this->paymentGateway);
    }

    protected function orderTickets($concert, $params){
        $response = $this->json('POST', "/concerts/{$concert->id}/orders", $params);
        return $response;
    }

    /** @test */
    public function customer_can_purchase_tickets_to_a_published_concert(){

        // $this->withoutExceptionHandling();
        $concert = factory(Concert::class)->states('published')->create([
            'ticket_price' => 3250,
        ]);

        $concert->addTickets(3);

        $response = $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $response->assertStatus(201);

        $response->assertJsonFragment([
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'amount' => 9750,
        ]);

        $this->assertEquals(9750, $this->paymentGateway->totalCharges());

        $order = $concert->orders()->where('email', 'john@example.com')->first();
        $this->assertNotNull($order);
        $this->assertEquals(3, $order->tickets()->count());
    }

    /** @test */
    public function cannot_purchase_tickets_to_an_unpublished_concert(){

        // $this->withExceptionHandling();
        $concert = factory(Concert::class)->states('unpublished')->create();
        $concert->addTickets(3);

        $response = $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $response->assertStatus(404);
        $this->assertEquals(0, $concert->orders()->count());
        $this->assertEquals(0, $this->paymentGateway->totalCharges());
    }

    /** @test */
    public function an_order_is_not_created_if_payment_fails(){

        $this->withExceptionHandling();
        $concert = factory(Concert::class)->states('published')->create([
            'ticket_price' => 3250,
        ]);

        $concert->addTickets(3);

        $response = $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => 'invalid-payment-token',
        ]);

        $response->assertStatus(422);
        $order = $concert->orders()->where('email', 'john@example.com')->first();
        $this->assertNull($order);

    }

    /** @test */
    public function email_is_required_to_purchase_tickets(){

        $concert = factory(Concert::class)->states('published')->create();

        $response = $this->orderTickets($concert, [
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);


        $response->assertJsonValidationErrors('email');
        // dd($response->json());
    }

    /** @test */
    public function cannot_purchase_more_tickets_than_remain(){

        // $this->withoutExceptionHandling();
        $concert = factory(Concert::class)->states('published')->create();

        $concert->addTickets(50);

        $response = $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 51,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $response->assertStatus(422);
        $order = $concert->orders()->where('email', 'john@example.com')->first();
        $this->assertNull($order);
        $this->assertEquals(0, $this->paymentGateway->totalCharges());
        $this->assertEquals(50, $concert->ticketsRemaining());
    }

    /** @test */
    public function email_must_be_valid_to_purchase_ticket(){

        $concert = factory(Concert::class)->states('published')->create();

        $response = $this->orderTickets($concert, [
            'email' => 'not-an-email-address',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $response->assertJsonValidationErrors('email');

    }

    /** @test */
    public function ticket_quantitiy_is_required_to_purchase_ticket(){

        $concert = factory(Concert::class)->states('published')->create();

        $response = $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $response->assertJsonValidationErrors('ticket_quantity');

    }

    /** @test */
    public function ticket_quantitiy_must_be_at_least_1_to_purchase_tickets(){

        $concert = factory(Concert::class)->states('published')->create();

        $response = $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 0,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $response->assertJsonValidationErrors('ticket_quantity');
        // dd($response->json());
    }

    /** @test */
    public function payment_token_is_required(){

        $concert = factory(Concert::class)->states('published')->create();

        $response = $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
        ]);

        $response->assertJsonValidationErrors('payment_token');
        // dd($response->json());
    }



}
