<?php

namespace App\Controllers;

use App\Models\Trip;
use App\Helpers\TurkeyCities;

class HomeController {
    
    public function index(): void {
        $filters = [];
        $hasSearch = false;
        
        // Check if any search parameter is provided
        if (isset($_GET['from_city']) || isset($_GET['to_city']) || isset($_GET['date'])) {
            $hasSearch = true;
            
            // Build filters only with non-empty values
            if (!empty($_GET['from_city'])) {
                $filters['from_city'] = trim($_GET['from_city']);
            }
            if (!empty($_GET['to_city'])) {
                $filters['to_city'] = trim($_GET['to_city']);
            }
            if (!empty($_GET['date'])) {
                $filters['date'] = trim($_GET['date']);
            }
        }
        
        // If search submitted (even with empty fields), show all trips
        // If no search at all, show empty state
        $trips = $hasSearch ? Trip::search($filters) : [];
        
        // Get all Turkey cities for dropdowns (81 il)
        $cities = TurkeyCities::all();
        
        require __DIR__ . '/../views/home.php';
    }
}

