<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Concert;
use App\Order;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class OrderTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function creating_an_order_from_tickets_and_email_and_amount(){

        $concert = factory(Concert::class)->create();
        $concert->addTickets(5);
        $this->assertEquals(5, $concert->ticketsRemaining());

        $order = Order::forTickets($concert->findTickets(3), 'john@example.com', 3600);

        $this->assertEquals('john@example.com', $order->email);
        $this->assertEquals(3, $order->ticketQuantity());
        $this->assertEquals(3600, $order->amount);
        $this->assertEquals(2, $concert->ticketsRemaining());

    }

    /** @test */
    public function converting_to_an_array(){

        $this->withoutExceptionHandling();
        $concert = factory(Concert::class)->create(['ticket_price' => 1200]);
        $concert->addTickets(5);
        $order = $concert->orderTickets('jane@example.com', 5);

        $result = $order->toArray();

        $this->assertEquals([
            'email' => 'jane@example.com',
            'ticket_quantity' => 5,
            'amount' => 6000,
        ], $result);
    }

    /** @test */
    public function tickets_are_realesed_when_an_order_is_cancelled(){

        $this->withExceptionHandling();

        $concert = factory(Concert::class)->create();

        $concert->addTickets(10);
        $order = $concert->orderTickets('jane@example.com', 5);

        $this->assertEquals(5, $concert->ticketsRemaining());

        $order->cancel();

        $this->assertEquals(10, $concert->ticketsRemaining());
        $this->assertNull(Order::find($order->id));
    }
}
