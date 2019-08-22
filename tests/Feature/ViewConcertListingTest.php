<?php

namespace Tests\Feature;

use App\Concert;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ViewConcertListingTest extends TestCase
{

    use DatabaseMigrations;

    /** @test */
    function user_can_view_published_concert_listing(){

        // Arrange
        // Create Concert
        $concert = factory(Concert::class)->states('published')->create([
            'title' => 'The Red Chord',
            'subtitle' => 'with Animosity and Lethargy',
            'date' => Carbon::parse('December 13, 2016 8:00pm'),
            'ticket_price' => 3250,
            'venue' => 'The Mosh Pit',
            'venue_address' => '123 Example Lane',
            'city' => 'Laraville',
            'state' => 'ON',
            'zip' => '17916',
            'additional_information' => 'for tickets, call (555) 555-5555',
        ]);

        // Act
        // View the concert listing
        $response = $this->get('/concerts/'. $concert->id);

        // Assert
        // See the concert details
        $response->assertSee('The Red Chord');
        $response->assertSee('with Animosity and Lethargy');
        $response->assertSee('December 13, 2016');
        $response->assertSee('8:00pm');
        $response->assertSee('32.50');
        $response->assertSee('The Mosh Pit');
        $response->assertSee('123 Example Lane');
        $response->assertSee('Laraville, ON 17916');
        $response->assertSee('for tickets, call (555) 555-5555.');

    }

    /** @test */
    public function user_cannot_view_unblished_concert_listings(){
        $concert = factory(Concert::class)->states('unpublished')->create();

        $response = $this->get('/concerts/'. $concert->id);

        //400
        $response->assertStatus(404);
    }

}