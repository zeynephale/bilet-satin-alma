<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Trip {
    public ?int $id = null;
    public int $firma_id;
    public string $from_city;
    public string $to_city;
    public string $date; // YYYY-MM-DD
    public string $time; // HH:MM
    public float $price;
    public int $seats;
    public string $bus_type = '2+2'; // 2+1, 2+2, 3+2
    
    public static function create(array $data): self {
        $trip = new self();
        $trip->firma_id = $data['firma_id'];
        $trip->from_city = $data['from_city'];
        $trip->to_city = $data['to_city'];
        $trip->date = $data['date'];
        $trip->time = $data['time'];
        $trip->price = $data['price'];
        $trip->seats = $data['seats'];
        $trip->bus_type = $data['bus_type'] ?? '2+2';
        
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "INSERT INTO trips (firma_id, from_city, to_city, date, time, price, seats, bus_type) 
             VALUES (:firma_id, :from_city, :to_city, :date, :time, :price, :seats, :bus_type)"
        );
        
        $stmt->execute([
            'firma_id' => $trip->firma_id,
            'from_city' => $trip->from_city,
            'to_city' => $trip->to_city,
            'date' => $trip->date,
            'time' => $trip->time,
            'price' => $trip->price,
            'seats' => $trip->seats,
            'bus_type' => $trip->bus_type
        ]);
        
        $trip->id = (int) $db->lastInsertId();
        return $trip;
    }
    
    public static function find(int $id): ?self {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM trips WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return self::hydrate($data);
    }
    
    public static function search(array $filters = []): array {
        $db = Database::getInstance();
        $query = "SELECT * FROM trips WHERE 1=1";
        $params = [];
        
        if (!empty($filters['from_city'])) {
            $query .= " AND from_city LIKE :from_city";
            $params['from_city'] = '%' . $filters['from_city'] . '%';
        }
        
        if (!empty($filters['to_city'])) {
            $query .= " AND to_city LIKE :to_city";
            $params['to_city'] = '%' . $filters['to_city'] . '%';
        }
        
        if (!empty($filters['date'])) {
            $query .= " AND date = :date";
            $params['date'] = $filters['date'];
        }
        
        if (isset($filters['firma_id'])) {
            $query .= " AND firma_id = :firma_id";
            $params['firma_id'] = $filters['firma_id'];
        }
        
        $query .= " ORDER BY date, time";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        $trips = [];
        while ($data = $stmt->fetch()) {
            $trips[] = self::hydrate($data);
        }
        
        return $trips;
    }
    
    public static function all(): array {
        return self::search();
    }
    
    public function update(): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "UPDATE trips SET firma_id = :firma_id, from_city = :from_city, 
             to_city = :to_city, date = :date, time = :time, price = :price, seats = :seats, bus_type = :bus_type 
             WHERE id = :id"
        );
        
        return $stmt->execute([
            'firma_id' => $this->firma_id,
            'from_city' => $this->from_city,
            'to_city' => $this->to_city,
            'date' => $this->date,
            'time' => $this->time,
            'price' => $this->price,
            'seats' => $this->seats,
            'bus_type' => $this->bus_type,
            'id' => $this->id
        ]);
    }
    
    public function delete(): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM trips WHERE id = :id");
        return $stmt->execute(['id' => $this->id]);
    }
    
    public function getOccupiedSeats(): array {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT seat_number FROM tickets 
             WHERE trip_id = :trip_id AND status = 'active' 
             ORDER BY seat_number"
        );
        $stmt->execute(['trip_id' => $this->id]);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    public function getAvailableSeatsCount(): int {
        return $this->seats - count($this->getOccupiedSeats());
    }
    
    /**
     * Get bus seat layout based on bus_type
     * Returns array with layout info
     */
    public function getBusLayout(): array {
        $layouts = [
            '2+1' => ['rows' => 12, 'columns' => 3, 'aisle_after' => 2], // 36 seats
            '2+2' => ['rows' => 11, 'columns' => 4, 'aisle_after' => 2], // 44 seats
            '3+2' => ['rows' => 9, 'columns' => 5, 'aisle_after' => 3],  // 45 seats
        ];
        
        return $layouts[$this->bus_type] ?? $layouts['2+2'];
    }
    
    /**
     * Get seat arrangement for visual display
     * Returns array of [seatNumber => position]
     */
    public function getSeatArrangement(): array {
        $layout = $this->getBusLayout();
        $seats = [];
        $seatNumber = 1;
        
        for ($row = 0; $row < $layout['rows']; $row++) {
            for ($col = 0; $col < $layout['columns']; $col++) {
                if ($seatNumber <= $this->seats) {
                    $seats[$seatNumber] = [
                        'row' => $row,
                        'col' => $col,
                        'is_aisle_after' => ($col + 1) == $layout['aisle_after']
                    ];
                    $seatNumber++;
                }
            }
        }
        
        return $seats;
    }
    
    private static function hydrate(array $data): self {
        $trip = new self();
        $trip->id = (int) $data['id'];
        $trip->firma_id = (int) $data['firma_id'];
        $trip->from_city = $data['from_city'];
        $trip->to_city = $data['to_city'];
        $trip->date = $data['date'];
        $trip->time = $data['time'];
        $trip->price = (float) $data['price'];
        $trip->seats = (int) $data['seats'];
        $trip->bus_type = $data['bus_type'] ?? '2+2';
        return $trip;
    }
}

