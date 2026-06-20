-- Seed: stop_stops 
INSERT INTO stop_stops (name, route_id, lat, lng, zone_id, sequence_order) VALUES

-- route 1
('Terminal Lebak Bulus',1,-6.2897154,106.7748231,3,1),
('Halte Fatmawati',1,-6.2920817,106.7926304,3,2),
('Halte Cipete Raya',1,-6.2789535,106.7974812,3,3),
('Halte Blok M',1,-6.2446481,106.7986129,3,4),
('Bundaran HI',1,-6.1949978,106.8229143,1,5),

-- route 2
('Terminal Pulo Gadung',2,-6.1849376,106.8992147,2,1),
('Halte Rawamangun',2,-6.1974165,106.8817158,2,2),
('Halte Matraman',2,-6.2118263,106.8665941,2,3),
('Halte Senen',2,-6.1765312,106.8419054,2,4),
('Terminal Harmoni',2,-6.1664871,106.8171837,1,5),

-- route 3
('Terminal Kalideres',3,-6.1375589,106.7039712,3,1),
('Halte Pesakih',3,-6.1479425,106.7203855,3,2),
('Halte Jembatan Baru',3,-6.1583321,106.7516403,3,3),
('Halte Grogol',3,-6.1615407,106.7904485,2,4),
('Terminal Pasar Baru',3,-6.1627506,106.8342139,1,5),

-- route 4
('Terminal Pulogadung',4,-6.1845113,106.8994725,2,1),
('Halte Pemuda',4,-6.1934867,106.8789301,2,2),
('Halte Pramuka',4,-6.2023187,106.8604405,2,3),
('Halte Kuningan',4,-6.2252641,106.8309488,2,4),
('Terminal Dukuh Atas',4,-6.2008427,106.8228772,1,5),

-- route 5
('Terminal Ancol',5,-6.1239176,106.8423198,1,1),
('Halte Gunung Sahari',5,-6.1496264,106.8429486,1,2),
('Halte Mangga Dua',5,-6.1377845,106.8236074,1,3),
('Halte Cempaka Putih',5,-6.1816282,106.8663321,2,4),
('Terminal Kampung Melayu',5,-6.2147593,106.8664757,2,5),

-- route 6
('Terminal Ragunan',6,-6.3023171,106.8202469,3,1),
('Halte Pasar Minggu',6,-6.2853877,106.8422104,3,2),
('Halte Pancoran',6,-6.2436931,106.8427149,2,3),
('Halte Setiabudi',6,-6.2216942,106.8248527,2,4),
('Terminal Dukuh Atas',6,-6.2008427,106.8228772,1,5),

-- route 7
('Terminal Kampung Rambutan',7,-6.3090174,106.8842732,3,1),
('Halte Ciracas',7,-6.2943115,106.8784338,3,2),
('Halte Cawang UKI',7,-6.2421847,106.8738917,2,3),
('Halte Bidara Cina',7,-6.2251786,106.8682375,2,4),
('Terminal Kampung Melayu',7,-6.2147593,106.8664757,2,5),

-- route 8
('Terminal Lebak Bulus',8,-6.2897154,106.7748231,3,1),
('Halte Pondok Indah',8,-6.2669803,106.7822158,3,2),
('Halte Senayan',8,-6.2270624,106.7993127,2,3),
('Halte Monas',8,-6.1753926,106.8271528,1,4),
('Terminal Harmoni',8,-6.1664871,106.8171837,1,5),

-- route 9
('Terminal Pinang Ranti',9,-6.2901135,106.8819158,3,1),
('Halte TMII',9,-6.2990725,106.8891244,3,2),
('Halte Cawang',9,-6.2421274,106.8668816,2,3),
('Halte Tomang',9,-6.1718619,106.7928488,2,4),
('Terminal Pluit',9,-6.1178063,106.7906397,1,5),

-- route 10
('Terminal Blok M',10,-6.2446481,106.7986129,3,1),
('Halte ASEAN',10,-6.2388634,106.7999816,3,2),
('Halte Menteng',10,-6.1944815,106.8387476,1,3),
('Halte Kemayoran',10,-6.1573727,106.8508913,1,4),
('Terminal Tanjung Priok',10,-6.1048235,106.8805447,1,5);


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

-- =============================================================================
-- FLEET SERVICE SEEDS
-- =============================================================================

-- Seed: fleet_routes (10 routes)
INSERT INTO fleet_routes (id, name, origin, destination, total_stops, distance_km, est_duration_min, created_at)
VALUES
(1,  'Rute Koridor 1',  'Terminal Lebak Bulus',   'Bundaran HI',       12, 24.50, 60,  NOW()),
(2,  'Rute Koridor 2',  'Pulo Gadung',             'Harmoni',           10, 18.30, 45,  NOW()),
(3,  'Rute Koridor 3',  'Kalideres',               'Pasar Baru',        11, 21.70, 55,  NOW()),
(4,  'Rute Koridor 4',  'Pulogadung',              'Dukuh Atas',         9, 16.80, 40,  NOW()),
(5,  'Rute Koridor 5',  'Ancol',                   'Kampung Melayu',     8, 14.20, 35,  NOW()),
(6,  'Rute Koridor 6',  'Ragunan',                 'Dukuh Atas',        10, 19.40, 50,  NOW()),
(7,  'Rute Koridor 7',  'Kampung Rambutan',        'Kampung Melayu',     7, 13.60, 32,  NOW()),
(8,  'Rute Koridor 8',  'Lebak Bulus',             'Harmoni',           13, 26.10, 65,  NOW()),
(9,  'Rute Koridor 9',  'Pinang Ranti',            'Pluit',             15, 29.80, 70,  NOW()),
(10, 'Rute Koridor 10', 'Terminal Blok M',         'Tanjung Priok',     14, 27.50, 68,  NOW())
ON DUPLICATE KEY UPDATE
    name             = VALUES(name),
    origin           = VALUES(origin),
    destination      = VALUES(destination),
    total_stops      = VALUES(total_stops),
    distance_km      = VALUES(distance_km),
    est_duration_min = VALUES(est_duration_min);

-- Seed: fleet_buses (30 buses — 3 buses per route, mapping: route_id = INTDIV(bus_id-1, 3) + 1)
INSERT INTO fleet_buses (id, plate_number, route_id, capacity, status, driver_name, created_at)
VALUES
-- Route 1 (bus 1–3)
(1,  'B 1001 UPN', 1,  40, 'active', 'Agus Salim',        NOW()),
(2,  'B 1002 UPN', 1,  50, 'active', 'Budi Hartono',      NOW()),
(3,  'B 1003 UPN', 1,  40, 'active', 'Cahyo Nugroho',     NOW()),
-- Route 2 (bus 4–6)
(4,  'B 1004 UPN', 2,  50, 'active', 'Dedi Kurniawan',    NOW()),
(5,  'B 1005 UPN', 2,  40, 'active', 'Eko Prasetyo',      NOW()),
(6,  'B 1006 UPN', 2,  50, 'active', 'Fajar Santoso',     NOW()),
-- Route 3 (bus 7–9)
(7,  'B 1007 UPN', 3,  40, 'active', 'Guntur Wibowo',     NOW()),
(8,  'B 1008 UPN', 3,  50, 'active', 'Hendra Wijaya',     NOW()),
(9,  'B 1009 UPN', 3,  40, 'active', 'Irfan Maulana',     NOW()),
-- Route 4 (bus 10–12)
(10, 'B 1010 UPN', 4,  50, 'active', 'Joko Susilo',       NOW()),
(11, 'B 1011 UPN', 4,  40, 'active', 'Kusuma Adi',        NOW()),
(12, 'B 1012 UPN', 4,  50, 'active', 'Lukman Hakim',      NOW()),
-- Route 5 (bus 13–15)
(13, 'B 1013 UPN', 5,  40, 'active', 'Muhamad Rizki',     NOW()),
(14, 'B 1014 UPN', 5,  50, 'active', 'Novan Setiawan',    NOW()),
(15, 'B 1015 UPN', 5,  40, 'active', 'Oscar Firmansyah',  NOW()),
-- Route 6 (bus 16–18)
(16, 'B 1016 UPN', 6,  50, 'active', 'Pandu Raharjo',     NOW()),
(17, 'B 1017 UPN', 6,  40, 'active', 'Qori Hidayat',      NOW()),
(18, 'B 1018 UPN', 6,  50, 'active', 'Rudi Hermawan',     NOW()),
-- Route 7 (bus 19–21)
(19, 'B 1019 UPN', 7,  40, 'active', 'Surya Darma',       NOW()),
(20, 'B 1020 UPN', 7,  50, 'active', 'Teguh Santoso',     NOW()),
(21, 'B 1021 UPN', 7,  40, 'active', 'Umar Fauzi',        NOW()),
-- Route 8 (bus 22–24)
(22, 'B 1022 UPN', 8,  50, 'active', 'Vino Pratama',      NOW()),
(23, 'B 1023 UPN', 8,  40, 'active', 'Wahyu Saputra',     NOW()),
(24, 'B 1024 UPN', 8,  50, 'active', 'Xander Putra',      NOW()),
-- Route 9 (bus 25–27)
(25, 'B 1025 UPN', 9,  40, 'active', 'Yoga Perdana',      NOW()),
(26, 'B 1026 UPN', 9,  50, 'active', 'Zulfikar Amin',     NOW()),
(27, 'B 1027 UPN', 9,  40, 'active', 'Andi Wirawan',      NOW()),
-- Route 10 (bus 28–30)
(28, 'B 1028 UPN', 10, 50, 'active', 'Bayu Setiawan',     NOW()),
(29, 'B 1029 UPN', 10, 40, 'active', 'Candra Lesmana',    NOW()),
(30, 'B 1030 UPN', 10, 50, 'active', 'Danu Haryanto',     NOW())
ON DUPLICATE KEY UPDATE
    plate_number = VALUES(plate_number),
    route_id     = VALUES(route_id),
    capacity     = VALUES(capacity),
    status       = VALUES(status),
    driver_name  = VALUES(driver_name);