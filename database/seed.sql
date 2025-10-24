-- Bilet Satın Alma Sistemi - Seed Data
-- Test için örnek veriler

-- Firms (Otobüs Firmaları)
INSERT INTO firms (name) VALUES ('Metro Turizm');
INSERT INTO firms (name) VALUES ('Pamukkale Turizm');
INSERT INTO firms (name) VALUES ('Kamil Koç');

-- Users
-- Admin: username=admin, password=admin123
INSERT INTO users (username, password_hash, role, firma_id, credit) VALUES 
('admin', '$2y$10$vFXSeG540CQxFoAPpBiv3.GIG0I/mkAS7JF5J9iO0iJT3xRvr.xmC', 'admin', NULL, 0);

-- Firma Admins
-- Metro Admin: username=metro_admin, password=metro123
INSERT INTO users (username, password_hash, role, firma_id, credit) VALUES 
('metro_admin', '$2y$10$nnp/s46OoPzHWNuqhKUfv.VLvwJLK6T5JyYR5gShSh2sjuGCxZxiy', 'firma_admin', 1, 0);

-- Pamukkale Admin: username=pamukkale_admin, password=pamukkale123
INSERT INTO users (username, password_hash, role, firma_id, credit) VALUES 
('pamukkale_admin', '$2y$10$DA8RIW94QjwX1JdP8L4OOe.YTWsyy9hyUqF3TXRGEhzUkBJ0t/wUG', 'firma_admin', 2, 0);

-- Kamil Koç Admin: username=kamilkoc_admin, password=kamilkoc123
INSERT INTO users (username, password_hash, role, firma_id, credit) VALUES 
('kamilkoc_admin', '$2y$10$B5NLAoSLuExa7h4PiJA88ecROVZQ.zkfQezBwSrI.SIgzbGgvSP1y', 'firma_admin', 3, 0);

-- Regular Users (Yolcular)
-- User1: username=yolcu1, password=yolcu123
INSERT INTO users (username, password_hash, role, firma_id, credit) VALUES 
('yolcu1', '$2y$10$m96GvdnrmI0ccXHHYLGY2Or2DeZMoiS5yeXvtjy5QvdjjgSpA6wP2', 'user', NULL, 5000);

-- User2: username=yolcu2, password=yolcu123
INSERT INTO users (username, password_hash, role, firma_id, credit) VALUES 
('yolcu2', '$2y$10$VRRgewEZ3w8uhn.LMRr7N.ptUKpdblBTJ3eNXGdfFSMcxhz9R3Ujy', 'user', NULL, 3000);

-- Trips (Seferler)
-- Dinamik tarihler: bugünden sonraki 5, 6, 7, 8 gün
-- Metro Turizm seferleri
INSERT INTO trips (firma_id, from_city, to_city, date, time, price, seats) VALUES 
(1, 'İstanbul', 'Ankara', date('now', '+5 days'), '09:00', 250.00, 44),
(1, 'İstanbul', 'İzmir', date('now', '+5 days'), '10:30', 300.00, 36),
(1, 'Ankara', 'İstanbul', date('now', '+6 days'), '14:00', 250.00, 44),
(1, 'İstanbul', 'Antalya', date('now', '+7 days'), '20:00', 400.00, 36);

-- Pamukkale Turizm seferleri
INSERT INTO trips (firma_id, from_city, to_city, date, time, price, seats) VALUES 
(2, 'İstanbul', 'Ankara', date('now', '+5 days'), '08:00', 240.00, 45),
(2, 'İzmir', 'Ankara', date('now', '+5 days'), '11:00', 220.00, 44),
(2, 'Ankara', 'Antalya', date('now', '+6 days'), '19:00', 350.00, 44),
(2, 'Bursa', 'İzmir', date('now', '+7 days'), '15:00', 180.00, 36);

-- Kamil Koç seferleri
INSERT INTO trips (firma_id, from_city, to_city, date, time, price, seats) VALUES 
(3, 'İstanbul', 'Trabzon', date('now', '+8 days'), '21:00', 500.00, 36),
(3, 'İstanbul', 'Samsun', date('now', '+8 days'), '22:00', 450.00, 45);

-- Global Coupons (Admin tarafından oluşturulmuş)
-- Dinamik son kullanma tarihleri
INSERT INTO coupons (code, discount_percent, usage_limit, used_count, expiry_date, firma_id) VALUES 
('WELCOME20', 20, 100, 0, date('now', '+90 days'), NULL),
('NEWYEAR25', 25, 50, 0, date('now', '+60 days'), NULL),
('SUMMER15', 15, 200, 0, date('now', '+30 days'), NULL);

-- Firma-specific Coupons
-- Metro Turizm kuponu
INSERT INTO coupons (code, discount_percent, usage_limit, used_count, expiry_date, firma_id) VALUES 
('METRO10', 10, 50, 0, date('now', '+45 days'), 1);

-- Pamukkale Turizm kuponu
INSERT INTO coupons (code, discount_percent, usage_limit, used_count, expiry_date, firma_id) VALUES 
('PAMUKKALE15', 15, 30, 0, date('now', '+45 days'), 2);

-- Sample Tickets (yolcu1'in birkaç bileti)
INSERT INTO tickets (user_id, trip_id, seat_number, status, created_at) VALUES 
(4, 1, 15, 'active', datetime('now')),
(4, 3, 22, 'active', datetime('now', '-2 days'));

-- Sample cancelled ticket
INSERT INTO tickets (user_id, trip_id, seat_number, status, created_at) VALUES 
(5, 2, 10, 'cancelled', datetime('now', '-5 days'));

