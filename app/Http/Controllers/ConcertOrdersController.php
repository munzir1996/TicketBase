<?php

namespace App\Http\Controllers;

use App\Concert;
use App\Order;
use Illuminate\Http\Request;
use App\Billing\PaymentGateway;
use App\Billing\FakePaymentGateway;
use App\Billing\PaymentFailedException;
use App\Exceptions\NotEnoughTicketsException;
use App\Reservation;

class ConcertOrdersController extends Controller
{

    private $paymentGateway;

    public function __construct(PaymentGateway $paymentGateway)
    {
        $this->paymentGateway =  $paymentGateway;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store($id)
    {
        $concert = Concert::published()->findOrFail($id);
        $this->validate(request(), [
            'email' => 'required|email',
            'ticket_quantity' => 'required|integer|min:1',
            'payment_token' => 'required',
        ]);

        try {

            //Find some tickets
            $tickets = $concert->findTickets(request('ticket_quantity'));
            $reservation = new Reservation($tickets);

            //Charge the customer for the tickets
            $this->paymentGateway->charge($reservation->totalCost(), request('payment_token'));

            //Create an order for those tickets
            $order = Order::forTickets($tickets, request('email'), $reservation->totalCost());

            return response()->json($order, 201);
        }catch(PaymentFailedException $e){

            return response()->json([], 422);
        }catch(NotEnoughTicketsException $e){

            return response()->json([], 422);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Concert  $concert
     * @return \Illuminate\Http\Response
     */
    public function show(Concert $concert)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Concert  $concert
     * @return \Illuminate\Http\Response
     */
    public function edit(Concert $concert)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Concert  $concert
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Concert $concert)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Concert  $concert
     * @return \Illuminate\Http\Response
     */
    public function destroy(Concert $concert)
    {
        //
    }
}
