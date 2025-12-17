<?php

namespace App\Modules\Operations\Actions\Reservations;

use App\Models\Activity\Reservation;

class CancelReservationAction
{
    public function execute(Reservation $reservation): void
    {
        // Simple delete for now. 
        // Future: Refund logic if paid?
        $reservation->delete();
    }
}
