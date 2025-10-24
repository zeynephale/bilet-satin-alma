<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use DateTime;

class Ticket {
    public ?int $id = null;
    public int $user_id;
    public int $trip_id;
    public int $seat_number;
    public string $status = 'active'; // active, cancelled
    public string $created_at;
    
    public static function create(array $data): self {
        $ticket = new self();
        $ticket->user_id = $data['user_id'];
        $ticket->trip_id = $data['trip_id'];
        $ticket->seat_number = $data['seat_number'];
        $ticket->status = 'active';
        $ticket->created_at = date('Y-m-d H:i:s');
        
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "INSERT INTO tickets (user_id, trip_id, seat_number, status, created_at) 
             VALUES (:user_id, :trip_id, :seat_number, :status, :created_at)"
        );
        
        $stmt->execute([
            'user_id' => $ticket->user_id,
            'trip_id' => $ticket->trip_id,
            'seat_number' => $ticket->seat_number,
            'status' => $ticket->status,
            'created_at' => $ticket->created_at
        ]);
        
        $ticket->id = (int) $db->lastInsertId();
        return $ticket;
    }
    
    public static function find(int $id): ?self {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM tickets WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();
        
        if (!$data) {
            return null;
        }
        
        return self::hydrate($data);
    }
    
    public static function findByUser(int $userId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT * FROM tickets WHERE user_id = :user_id ORDER BY created_at DESC"
        );
        $stmt->execute(['user_id' => $userId]);
        
        $tickets = [];
        while ($data = $stmt->fetch()) {
            $tickets[] = self::hydrate($data);
        }
        
        return $tickets;
    }
    
    public function cancel(): bool {
        // Check if cancellation is allowed (at least 1 hour before trip)
        $trip = Trip::find($this->trip_id);
        if (!$trip) {
            return false;
        }
        
        $tripDateTime = new DateTime($trip->date . ' ' . $trip->time);
        $now = new DateTime();
        $diff = $tripDateTime->getTimestamp() - $now->getTimestamp();
        
        // Must be at least 1 hour (3600 seconds) before trip
        if ($diff < 3600) {
            return false;
        }
        
        $this->status = 'cancelled';
        
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE tickets SET status = :status WHERE id = :id");
        return $stmt->execute(['status' => $this->status, 'id' => $this->id]);
    }
    
    public function getTrip(): ?Trip {
        return Trip::find($this->trip_id);
    }
    
    public function getUser(): ?User {
        return User::find($this->user_id);
    }
    
    public function canBeCancelled(): bool {
        if ($this->status !== 'active') {
            return false;
        }
        
        $trip = $this->getTrip();
        if (!$trip) {
            return false;
        }
        
        $tripDateTime = new DateTime($trip->date . ' ' . $trip->time);
        $now = new DateTime();
        $diff = $tripDateTime->getTimestamp() - $now->getTimestamp();
        
        return $diff >= 3600; // At least 1 hour
    }
    
    private static function hydrate(array $data): self {
        $ticket = new self();
        $ticket->id = (int) $data['id'];
        $ticket->user_id = (int) $data['user_id'];
        $ticket->trip_id = (int) $data['trip_id'];
        $ticket->seat_number = (int) $data['seat_number'];
        $ticket->status = $data['status'];
        $ticket->created_at = $data['created_at'];
        return $ticket;
    }
}

