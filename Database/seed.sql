-- Seed: stop_stops 
INSERT INTO stop_stops (name, route_id, lat, lng, zone_id, sequence_order) VALUES
('Halte Bogor', 1, -6.1754, 106.8272, 1, 1),
('Halte Cilebut', 1, -6.1682, 106.8155, 1, 2),
('Halte Senen', 2, -6.1764, 106.8452, 2, 1),
('Halte Cawang', 2, -6.2424, 106.8681, 2, 2),
('Halte Blok M', 3, -6.2446, 106.7986, 3, 1),
('Halte Sudirman', 3, -6.2088, 106.8230, 3, 2),
('Halte Kuningan', 4, -6.2250, 106.8310, 2, 1),
('Halte Dukuh Atas', 4, -6.2015, 106.8230, 1, 2),s
('Halte Tanah Abang', 5, -6.1861, 106.8118, 1, 1),
('Halte Palmerah', 5, -6.2050, 106.7971, 3, 2);

-- Seed: passenger_passengers
INSERT INTO passenger_passengers (name, email, phone, card_number, balance, zone_id, role, password) VALUES
('Budi Santoso', 'budi@mail.com', '081234567890', 'CARD-001', 150000, 1, 'passenger', '$2y$10$dummyhash1'),
('Siti Rahayu', 'siti@mail.com', '081234567891', 'CARD-002', 200000, 2, 'passenger', '$2y$10$dummyhash2'),
('Ahmad Fauzi', 'ahmad@mail.com', '081234567892', 'CARD-003', 75000, 1, 'passenger', '$2y$10$dummyhash3'),
('Dewi Lestari', 'dewi@mail.com', '081234567893', 'CARD-004', 300000, 3, 'passenger', '$2y$10$dummyhash4'),
('Rizky Pratama', 'rizky@mail.com', '081234567894', 'CARD-005', 50000, 2, 'passenger', '$2y$10$dummyhash5');

-- Seed: passenger_tickets
INSERT INTO passenger_tickets (passenger_id, route_id, origin_stop_id, dest_stop_id, status, price) VALUES
(1, 1, 1, 2, 'used', 3500),
(2, 2, 3, 4, 'active', 4000),
(3, 3, 5, 6, 'used', 3500),
(1, 4, 7, 8, 'active', 4500),
(4, 5, 9, 10, 'cancelled', 3000);

-- Seed: passenger_notifications
INSERT INTO passenger_notifications (passenger_id, title, body, type, is_read) VALUES
(1, 'Bus Terlambat', 'Bus rute 1 diperkirakan terlambat 10 menit', 'delay', 0),
(2, 'Tiket Berhasil', 'Tiket perjalanan kamu berhasil dibeli', 'ticket', 1),
(3, 'Halte Padat', 'Halte Monas sedang sangat padat, pertimbangkan halte alternatif', 'anomaly', 0);

-- Seed: stop_passenger_counts
INSERT INTO stop_passenger_counts (stop_id, bus_id, boarded, alighted, current_load) VALUES
(1, 1, 15, 5, 30),
(2, 1, 10, 12, 28),
(3, 2, 20, 3, 45),
(4, 2, 8, 15, 38),
(5, 3, 25, 10, 50);

-- Seed: stop_alerts
INSERT INTO stop_alerts (stop_id, alert_type, severity, message, threshold) VALUES
(3, 'crowded', 'high', 'Halte Senen melebihi kapasitas normal', 40),
(1, 'delay', 'medium', 'Bus di Halte Monas terlambat lebih dari 15 menit', NULL);