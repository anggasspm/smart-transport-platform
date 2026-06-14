-- =============================================================
-- Smart Transport Platform - Complete Seed
-- docker-compose mounts: ./database/seed.sql
-- =============================================================

-- ─── STOP SERVICE seed ───────────────────────────────────────

INSERT INTO `stop_stops` (`name`, `route_id`, `lat`, `lng`, `zone_id`, `sequence_order`) VALUES
('Halte Blok M',       1, -6.2446,  106.7986, 3, 1),
('Halte Senayan',      1, -6.2264,  106.8010, 2, 2),
('Halte Sudirman',     1, -6.2088,  106.8230, 1, 3),
('Halte Bundaran HI',  1, -6.1944,  106.8229, 1, 4),
('Halte Senen',        2, -6.1764,  106.8452, 2, 1),
('Halte Cawang',       2, -6.2424,  106.8681, 2, 2),
('Halte Kuningan',     3, -6.2250,  106.8310, 2, 1),
('Halte Dukuh Atas',   3, -6.2015,  106.8230, 1, 2),
('Halte Tanah Abang',  4, -6.1861,  106.8118, 1, 1),
('Halte Palmerah',     4, -6.2050,  106.7971, 3, 2);

-- ─── PASSENGER SERVICE seed ──────────────────────────────────

INSERT INTO `passenger_passengers`
    (`name`, `email`, `phone`, `card_number`, `balance`, `zone_id`, `role`, `password`)
VALUES
('Budi Santoso',  'budi@mail.com',  '081234567890', 'CARD-001', 150000, 1, 'passenger', '$2y$10$dummyhash1'),
('Siti Rahayu',   'siti@mail.com',  '081234567891', 'CARD-002', 200000, 2, 'passenger', '$2y$10$dummyhash2'),
('Ahmad Fauzi',   'ahmad@mail.com', '081234567892', 'CARD-003',  75000, 1, 'passenger', '$2y$10$dummyhash3'),
('Dewi Lestari',  'dewi@mail.com',  '081234567893', 'CARD-004', 300000, 3, 'passenger', '$2y$10$dummyhash4'),
('Rizky Pratama', 'rizky@mail.com', '081234567894', 'CARD-005',  50000, 2, 'passenger', '$2y$10$dummyhash5');

INSERT INTO `passenger_tickets`
    (`passenger_id`, `route_id`, `origin_stop_id`, `dest_stop_id`, `status`, `price`)
VALUES
(1, 1, 1, 3, 'used',      3500),
(2, 2, 5, 6, 'active',    4000),
(3, 1, 2, 4, 'used',      3500),
(1, 3, 7, 8, 'active',    4500),
(4, 4, 9, 10,'cancelled', 3000);

INSERT INTO `passenger_notifications`
    (`passenger_id`, `title`, `body`, `type`, `is_read`)
VALUES
(1, 'Bus Terlambat',   'Bus rute 1 diperkirakan terlambat 10 menit',                    'delay',   0),
(2, 'Tiket Berhasil',  'Tiket perjalanan kamu berhasil dibeli',                         'ticket',  1),
(3, 'Halte Padat',     'Halte Monas sedang sangat padat, pertimbangkan halte lain',     'anomaly', 0);

INSERT INTO `stop_passenger_counts`
    (`stop_id`, `bus_id`, `boarded`, `alighted`, `current_load`)
VALUES
(1, 1, 15, 5,  30),
(2, 1, 10, 12, 28),
(3, 2, 20, 3,  45),
(4, 2, 8,  15, 38),
(5, 3, 25, 10, 50);

INSERT INTO `stop_alerts`
    (`stop_id`, `alert_type`, `severity`, `message`, `threshold`)
VALUES
(3, 'crowded', 'high',   'Halte Senen melebihi kapasitas normal',              40),
(1, 'delay',   'medium', 'Bus di Halte Blok M terlambat lebih dari 15 menit', NULL);

-- ─── FLEET SERVICE seed ──────────────────────────────────────
-- 10 Routes + 30 Buses
-- Mapping: route_id = intdiv(bus_id - 1, 3) + 1

INSERT INTO `fleet_routes`
    (`id`, `name`, `origin`, `destination`, `total_stops`, `distance_km`, `est_duration_min`)
VALUES
    (1,  'Koridor 1 - Blok M - Kota',                 'Blok M',          'Kota',              18, 23.40,  90),
    (2,  'Koridor 2 - Pulogadung - Harmoni',           'Pulogadung',      'Harmoni',           16, 19.10,  75),
    (3,  'Koridor 3 - Kalideres - Pasar Baru',         'Kalideres',       'Pasar Baru',        17, 24.00,  85),
    (4,  'Koridor 4 - Pulogadung - Dukuh Atas',        'Pulogadung',      'Dukuh Atas',        14, 11.90,  55),
    (5,  'Koridor 5 - Kampung Melayu - Ancol',         'Kampung Melayu',  'Ancol',             14, 13.50,  60),
    (6,  'Koridor 6 - Ragunan - Dukuh Atas',           'Ragunan',         'Dukuh Atas',        13, 12.30,  58),
    (7,  'Koridor 7 - Kampung Rambutan - Tanah Abang', 'Kampung Rambutan','Tanah Abang',       14, 16.50,  70),
    (8,  'Koridor 8 - Lebak Bulus - Harmoni',          'Lebak Bulus',     'Harmoni',           24, 26.30, 100),
    (9,  'Koridor 9 - Pluit - Pinang Ranti',           'Pluit',           'Pinang Ranti',      29, 29.90, 120),
    (10, 'Koridor 10 - Tanjung Priok - Cililitan',     'Tanjung Priok',   'Cililitan',         10,  9.60,  45);

INSERT INTO `fleet_buses`
    (`id`, `plate_number`, `route_id`, `capacity`, `status`, `driver_name`)
VALUES
    -- Route 1 (bus 1-3)
    ( 1, 'B 1001 RTA', 1, 40, 'active', 'Ahmad Fauzi'),
    ( 2, 'B 1002 RTA', 1, 40, 'active', 'Budi Santoso'),
    ( 3, 'B 1003 RTA', 1, 40, 'active', 'Cahyo Purnomo'),
    -- Route 2 (bus 4-6)
    ( 4, 'B 2001 RTB', 2, 40, 'active', 'Dedi Kusuma'),
    ( 5, 'B 2002 RTB', 2, 40, 'active', 'Eko Wahyudi'),
    ( 6, 'B 2003 RTB', 2, 40, 'active', 'Faisal Rahman'),
    -- Route 3 (bus 7-9)
    ( 7, 'B 3001 RTC', 3, 40, 'active', 'Gunawan Setiawan'),
    ( 8, 'B 3002 RTC', 3, 40, 'active', 'Hendra Wijaya'),
    ( 9, 'B 3003 RTC', 3, 40, 'active', 'Irwan Saputra'),
    -- Route 4 (bus 10-12)
    (10, 'B 4001 RTD', 4, 40, 'active', 'Joko Susilo'),
    (11, 'B 4002 RTD', 4, 40, 'active', 'Kurniawan Adi'),
    (12, 'B 4003 RTD', 4, 40, 'active', 'Lutfi Hakim'),
    -- Route 5 (bus 13-15)
    (13, 'B 5001 RTE', 5, 40, 'active', 'Muhammad Rizki'),
    (14, 'B 5002 RTE', 5, 40, 'active', 'Nugroho Wibowo'),
    (15, 'B 5003 RTE', 5, 40, 'active', 'Oki Pratama'),
    -- Route 6 (bus 16-18)
    (16, 'B 6001 RTF', 6, 40, 'active', 'Pandu Kristianto'),
    (17, 'B 6002 RTF', 6, 40, 'active', 'Qodir Mansyur'),
    (18, 'B 6003 RTF', 6, 40, 'active', 'Rendi Firmansyah'),
    -- Route 7 (bus 19-21)
    (19, 'B 7001 RTG', 7, 40, 'active', 'Surya Dharma'),
    (20, 'B 7002 RTG', 7, 40, 'active', 'Teguh Prasetyo'),
    (21, 'B 7003 RTG', 7, 40, 'active', 'Umar Bahari'),
    -- Route 8 (bus 22-24)
    (22, 'B 8001 RTH', 8, 40, 'active', 'Vicky Nugraha'),
    (23, 'B 8002 RTH', 8, 40, 'active', 'Wahyu Sanjaya'),
    (24, 'B 8003 RTH', 8, 40, 'active', 'Xaverius Anto'),
    -- Route 9 (bus 25-27)
    (25, 'B 9001 RTI', 9, 40, 'active', 'Yusuf Halim'),
    (26, 'B 9002 RTI', 9, 40, 'active', 'Zainal Abidin'),
    (27, 'B 9003 RTI', 9, 40, 'active', 'Arif Budiman'),
    -- Route 10 (bus 28-30)
    (28, 'B 0001 RTJ', 10, 40, 'active', 'Bagas Permana'),
    (29, 'B 0002 RTJ', 10, 40, 'active', 'Candra Lesmana'),
    (30, 'B 0003 RTJ', 10, 40, 'active', 'Dimas Prasetya');