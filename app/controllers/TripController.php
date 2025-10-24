<?php

namespace App\Controllers;

use App\Models\Trip;
use App\Models\Firm;
use App\Core\Response;

class TripController {
    
    public function show(string $id): void {
        $trip = Trip::find((int) $id);
        
        if (!$trip) {
            Response::notFound();
        }
        
        $firm = Firm::find($trip->firma_id);
        $occupiedSeats = $trip->getOccupiedSeats();
        $availableSeats = $trip->getAvailableSeatsCount();
        
        require __DIR__ . '/../views/trips/detail.php';
    }
}

