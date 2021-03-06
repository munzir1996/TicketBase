<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Exceptions\NotEnoughTicketsException;
class Concert extends Model
{
    protected $guarded = [];

    public function scopePublished($query){
        return $query->whereNotNull('published_at');
    }

    public function getFormattedDateAttribute(){
        return $this->date->format('F j, Y');
    }

    public function getFormattedStartTimeAttribute(){
        return $this->date->format('g:ia');
    }

    public function getTicketPriceInDollarsAttribute(){
        return number_format($this->ticket_price / 100, 2);
    }

    public function orderTickets($email, $ticketQuantity){

        $tickets = $this->findTickets($ticketQuantity);

        return $this->createOrder($email, $tickets);
    }

    public function findTickets($quantitiy){

        $tickets = $this->tickets()->available()->take($quantitiy)->get();
        if ($tickets->count() < $quantitiy) {
            throw new NotEnoughTicketsException;
        }

        return $tickets;
    }

    public function createOrder($email, $tickets){

        return Order::forTickets($tickets, $email, $tickets->sum('price'));
    }

    public function orders(){
        return $this->belongsToMany(Order::class, 'tickets');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function addTickets($quantitiy){

        foreach (range(1, $quantitiy) as $i) {
            $this->tickets()->create([]);
        }

    }

    public function ticketsRemaining()
    {
        return $this->tickets()->available()->count();
    }
}
