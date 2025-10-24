-- Bilet Satın Alma Sistemi - Database Schema
-- SQLite veritabanı şeması

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('admin', 'firma_admin', 'user')),
    firma_id INTEGER DEFAULT NULL,
    credit REAL DEFAULT 0.0,
    FOREIGN KEY (firma_id) REFERENCES firms(id) ON DELETE SET NULL
);

-- Firms Table
CREATE TABLE IF NOT EXISTS firms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);

-- Trips Table
CREATE TABLE IF NOT EXISTS trips (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    firma_id INTEGER NOT NULL,
    from_city TEXT NOT NULL,
    to_city TEXT NOT NULL,
    date TEXT NOT NULL,
    time TEXT NOT NULL,
    price REAL NOT NULL,
    seats INTEGER NOT NULL,
    FOREIGN KEY (firma_id) REFERENCES firms(id) ON DELETE CASCADE
);

-- Tickets Table
CREATE TABLE IF NOT EXISTS tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    trip_id INTEGER NOT NULL,
    seat_number INTEGER NOT NULL,
    status TEXT NOT NULL CHECK(status IN ('active', 'cancelled')) DEFAULT 'active',
    created_at TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
);

-- Coupons Table
CREATE TABLE IF NOT EXISTS coupons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    discount_percent REAL NOT NULL,
    usage_limit INTEGER NOT NULL,
    used_count INTEGER DEFAULT 0,
    expiry_date TEXT NOT NULL,
    firma_id INTEGER DEFAULT NULL,
    FOREIGN KEY (firma_id) REFERENCES firms(id) ON DELETE CASCADE
);

-- Indexes for better performance
CREATE INDEX IF NOT EXISTS idx_trips_date ON trips(date);
CREATE INDEX IF NOT EXISTS idx_trips_firma_id ON trips(firma_id);
CREATE INDEX IF NOT EXISTS idx_tickets_user_id ON tickets(user_id);
CREATE INDEX IF NOT EXISTS idx_tickets_trip_id ON tickets(trip_id);
CREATE INDEX IF NOT EXISTS idx_tickets_status ON tickets(status);
CREATE INDEX IF NOT EXISTS idx_coupons_code ON coupons(code);

-- Trigger to prevent double booking (seat conflict prevention)
-- Ensures same trip_id + seat_number with status='active' cannot exist twice
CREATE TRIGGER IF NOT EXISTS prevent_double_booking
BEFORE INSERT ON tickets
BEGIN
    SELECT CASE
        WHEN EXISTS (
            SELECT 1 FROM tickets 
            WHERE trip_id = NEW.trip_id 
            AND seat_number = NEW.seat_number 
            AND status = 'active'
        )
        THEN RAISE(ABORT, 'Seat already booked')
    END;
END;

-- Trigger to prevent reactivating a cancelled seat that's already taken
CREATE TRIGGER IF NOT EXISTS prevent_seat_reactivation
BEFORE UPDATE ON tickets
BEGIN
    SELECT CASE
        WHEN NEW.status = 'active' AND OLD.status = 'cancelled'
        AND EXISTS (
            SELECT 1 FROM tickets 
            WHERE trip_id = NEW.trip_id 
            AND seat_number = NEW.seat_number 
            AND status = 'active'
            AND id != NEW.id
        )
        THEN RAISE(ABORT, 'Seat already booked by another ticket')
    END;
END;

