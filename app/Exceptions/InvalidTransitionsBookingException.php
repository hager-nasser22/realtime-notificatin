<?php

namespace App\Exceptions;

use App\Enums\BookingStatus;
use Exception;

class InvalidTransitionsBookingException extends Exception
{
    public function __construct(BookingStatus $currentStatus , BookingStatus $newStatus){
        parent::__construct("invalid transition from {$currentStatus->value} to {$newStatus->value}");
    }
    public function render(){
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
