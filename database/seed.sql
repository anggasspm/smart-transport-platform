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
('Rizky Pratama', 'rizky@mail.com', '081234567894', 'CARD-005', 50000, 2, 'passenger', '$2y$10$dummyhash5'),

('Andi Saputra', 'andi@mail.com', '081234567895', 'CARD-006', 125000, 1, 'passenger', '$2y$10$dummyhash6'),
('Rina Marlina', 'rina@mail.com', '081234567896', 'CARD-007', 180000, 2, 'passenger', '$2y$10$dummyhash7'),
('Fajar Nugroho', 'fajar@mail.com', '081234567897', 'CARD-008', 90000, 1, 'passenger', '$2y$10$dummyhash8'),
('Nina Oktavia', 'nina@mail.com', '081234567898', 'CARD-009', 220000, 3, 'passenger', '$2y$10$dummyhash9'),
('Dimas Wijaya', 'dimas@mail.com', '081234567899', 'CARD-010', 110000, 2, 'passenger', '$2y$10$dummyhash10'),
('Putri Ayu', 'putri@mail.com', '081234567900', 'CARD-011', 275000, 1, 'passenger', '$2y$10$dummyhash11'),
('Yusuf Maulana', 'yusuf@mail.com', '081234567901', 'CARD-012', 85000, 2, 'passenger', '$2y$10$dummyhash12'),
('Intan Permata', 'intan@mail.com', '081234567902', 'CARD-013', 160000, 3, 'passenger', '$2y$10$dummyhash13'),
('Bayu Ramadhan', 'bayu@mail.com', '081234567903', 'CARD-014', 95000, 1, 'passenger', '$2y$10$dummyhash14'),
('Maya Sari', 'maya@mail.com', '081234567904', 'CARD-015', 240000, 2, 'passenger', '$2y$10$dummyhash15'),
('Arif Hidayat', 'arif@mail.com', '081234567905', 'CARD-016', 130000, 1, 'passenger', '$2y$10$dummyhash16'),
('Lina Kartika', 'lina@mail.com', '081234567906', 'CARD-017', 190000, 3, 'passenger', '$2y$10$dummyhash17'),
('Reza Kurniawan', 'reza@mail.com', '081234567907', 'CARD-018', 70000, 2, 'passenger', '$2y$10$dummyhash18'),
('Tika Puspita', 'tika@mail.com', '081234567908', 'CARD-019', 210000, 1, 'passenger', '$2y$10$dummyhash19'),
('Hendra Gunawan', 'hendra@mail.com', '081234567909', 'CARD-020', 145000, 2, 'passenger', '$2y$10$dummyhash20'),
('Wulan Safitri', 'wulan@mail.com', '081234567910', 'CARD-021', 260000, 3, 'passenger', '$2y$10$dummyhash21'),
('Eko Prasetyo', 'eko@mail.com', '081234567911', 'CARD-022', 100000, 1, 'passenger', '$2y$10$dummyhash22'),
('Fitri Handayani', 'fitri@mail.com', '081234567912', 'CARD-023', 175000, 2, 'passenger', '$2y$10$dummyhash23'),
('Galih Putra', 'galih@mail.com', '081234567913', 'CARD-024', 80000, 1, 'passenger', '$2y$10$dummyhash24'),
('Ayu Maharani', 'ayum@mail.com', '081234567914', 'CARD-025', 235000, 3, 'passenger', '$2y$10$dummyhash25'),
('Rendy Firmansyah', 'rendy@mail.com', '081234567915', 'CARD-026', 120000, 2, 'passenger', '$2y$10$dummyhash26'),
('Citra Dewanti', 'citra@mail.com', '081234567916', 'CARD-027', 195000, 1, 'passenger', '$2y$10$dummyhash27'),
('Agus Salim', 'agus@mail.com', '081234567917', 'CARD-028', 65000, 2, 'passenger', '$2y$10$dummyhash28'),
('Nabila Zahra', 'nabila@mail.com', '081234567918', 'CARD-029', 285000, 3, 'passenger', '$2y$10$dummyhash29'),
('Joko Susilo', 'joko@mail.com', '081234567919', 'CARD-030', 140000, 1, 'passenger', '$2y$10$dummyhash30'),
('Selvi Ananda', 'selvi@mail.com', '081234567920', 'CARD-031', 225000, 2, 'passenger', '$2y$10$dummyhash31'),
('Ilham Akbar', 'ilham@mail.com', '081234567921', 'CARD-032', 105000, 1, 'passenger', '$2y$10$dummyhash32'),
('Novi Yuliana', 'novi@mail.com', '081234567922', 'CARD-033', 170000, 3, 'passenger', '$2y$10$dummyhash33'),
('Farhan Hakim', 'farhan@mail.com', '081234567923', 'CARD-034', 92000, 2, 'passenger', '$2y$10$dummyhash34'),
('Desy Amelia', 'desy@mail.com', '081234567924', 'CARD-035', 250000, 1, 'passenger', '$2y$10$dummyhash35'),
('Rafi Aditya', 'rafi@mail.com', '081234567925', 'CARD-036', 135000, 2, 'passenger', '$2y$10$dummyhash36'),
('Shinta Larasati', 'shinta@mail.com', '081234567926', 'CARD-037', 205000, 3, 'passenger', '$2y$10$dummyhash37'),
('Bagas Mahendra', 'bagas@mail.com', '081234567927', 'CARD-038', 78000, 1, 'passenger', '$2y$10$dummyhash38'),
('Vina Melati', 'vina@mail.com', '081234567928', 'CARD-039', 230000, 2, 'passenger', '$2y$10$dummyhash39'),
('Aldo Sapri', 'aldo@mail.com', '081234567929', 'CARD-040', 118000, 1, 'passenger', '$2y$10$dummyhash40'),
('Mega Kusuma', 'mega@mail.com', '081234567930', 'CARD-041', 265000, 3, 'passenger', '$2y$10$dummyhash41'),
('Doni Setiawan', 'doni@mail.com', '081234567931', 'CARD-042', 99000, 2, 'passenger', '$2y$10$dummyhash42'),
('Yuni Astuti', 'yuni@mail.com', '081234567932', 'CARD-043', 185000, 1, 'passenger', '$2y$10$dummyhash43'),
('Iqbal Ramadhan', 'iqbal@mail.com', '081234567933', 'CARD-044', 88000, 2, 'passenger', '$2y$10$dummyhash44'),
('Puspita Sari', 'puspita@mail.com', '081234567934', 'CARD-045', 245000, 3, 'passenger', '$2y$10$dummyhash45'),
('Rama Prakoso', 'rama@mail.com', '081234567935', 'CARD-046', 150000, 1, 'passenger', '$2y$10$dummyhash46'),
('Tiara Anjani', 'tiara@mail.com', '081234567936', 'CARD-047', 215000, 2, 'passenger', '$2y$10$dummyhash47'),
('Wahyu Firmanto', 'wahyu@mail.com', '081234567937', 'CARD-048', 97000, 1, 'passenger', '$2y$10$dummyhash48'),
('Nanda Pratiwi', 'nanda@mail.com', '081234567938', 'CARD-049', 280000, 3, 'passenger', '$2y$10$dummyhash49'),
('Kevin Christian', 'kevin@mail.com', '081234567939', 'CARD-050', 155000, 2, 'passenger', '$2y$10$dummyhash50'),

('Jack', 'jack@mail.com', '085234517895', '01020304', 125000, 1, 'passenger', '$2y$10$dummyhash51'),
('Johannes', 'johan@mail.com', '08233567896', '11223344', 180000, 2, 'passenger', '$2y$10$dummyhash52'),
('Napoleon', 'napoleon@mail.com', '081234567897', '55667788', 90000, 1, 'passenger', '$2y$10$dummyhash53'),
('Copernicus', 'caper@mail.com', '085234567898', 'AABBCCDD', 275000, 3, 'passenger', '$2y$10$dummyhash54'),
('Daniel', 'daniel@mail.com', '085534567899', 'C0FFEE99', 60000, 2, 'passenger', '$2y$10$dummyhash55'),
('Lucas', 'lucas@mail.com', '083234567900', '04112233', 220000, 1, 'passenger', '$2y$10$dummyhash56');

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

/* Seed: stop_passenger_counts
INSERT INTO stop_passenger_counts (stop_id, bus_id, boarded, alighted, current_load) VALUES
(1, 1, 15, 5, 30),
(2, 1, 10, 12, 28),
(3, 2, 20, 3, 45),
(4, 2, 8, 15, 38),
(5, 3, 25, 10, 50); */

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