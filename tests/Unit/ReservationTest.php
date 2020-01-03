<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Concert;
use App\Reservation;
use Faker\Test\Provider\Collection;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ReservationTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function calculating_the_total_cost(){

        $tickets = collect([
           (object) ['price' => 1200],
           (object) ['price' => 1200],
           (object) ['price' => 1200],
        ]);

        $reservation = new Reservation($tickets);

        $this->assertEquals(3600, $reservation->totalCost());
    }
}
