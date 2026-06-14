-- =============================================================
-- Fleet Service Seed
-- 10 Routes + 30 Buses
-- Mapping: route_id = intdiv(bus_id - 1, 3) + 1
--   Bus 1-3   -> Route 1
--   Bus 4-6   -> Route 2
--   ...
--   Bus 28-30 -> Route 10
-- =============================================================

-- -------------------------------------------------------------
-- Seed fleet_routes (10 rute)
-- -------------------------------------------------------------
INSERT INTO `fleet_routes`
    (`id`, `name`, `origin`, `destination`, `total_stops`, `distance_km`, `est_duration_min`)
VALUES
    (1,  'Koridor 1 - Blok M - Kota',         'Blok M',          'Kota',              18, 23.40,  90),
    (2,  'Koridor 2 - Pulogadung - Harmoni',   'Pulogadung',      'Harmoni',           16, 19.10,  75),
    (3,  'Koridor 3 - Kalideres - Pasar Baru', 'Kalideres',       'Pasar Baru',        17, 24.00,  85),
    (4,  'Koridor 4 - Pulogadung - Dukuh Atas','Pulogadung',      'Dukuh Atas',        14, 11.90,  55),
    (5,  'Koridor 5 - Kampung Melayu - Ancol', 'Kampung Melayu',  'Ancol',             14, 13.50,  60),
    (6,  'Koridor 6 - Ragunan - Dukuh Atas',   'Ragunan',         'Dukuh Atas',        13, 12.30,  58),
    (7,  'Koridor 7 - Kampung Rambutan - Tanah Abang', 'Kampung Rambutan', 'Tanah Abang', 14, 16.50, 70),
    (8,  'Koridor 8 - Lebak Bulus - Harmoni',  'Lebak Bulus',     'Harmoni',           24, 26.30, 100),
    (9,  'Koridor 9 - Pluit - Pinang Ranti',   'Pluit',           'Pinang Ranti',      29, 29.90, 120),
    (10, 'Koridor 10 - Tanjung Priok - Cililitan', 'Tanjung Priok','Cililitan',        10,  9.60,  45);

-- -------------------------------------------------------------
-- Seed fleet_buses (30 bus, 3 per rute)
-- Plate format: B XXXX RTE (RTE = route number 2-digit)
-- -------------------------------------------------------------
INSERT INTO `fleet_buses`
    (`id`, `plate_number`, `route_id`, `capacity`, `status`, `driver_name`)
VALUES
    -- Route 1
    ( 1, 'B 1001 RTA', 1, 40, 'active', 'Ahmad Fauzi'),
    ( 2, 'B 1002 RTA', 1, 40, 'active', 'Budi Santoso'),
    ( 3, 'B 1003 RTA', 1, 40, 'active', 'Cahyo Purnomo'),
    -- Route 2
    ( 4, 'B 2001 RTB', 2, 40, 'active', 'Dedi Kusuma'),
    ( 5, 'B 2002 RTB', 2, 40, 'active', 'Eko Wahyudi'),
    ( 6, 'B 2003 RTB', 2, 40, 'active', 'Faisal Rahman'),
    -- Route 3
    ( 7, 'B 3001 RTC', 3, 40, 'active', 'Gunawan Setiawan'),
    ( 8, 'B 3002 RTC', 3, 40, 'active', 'Hendra Wijaya'),
    ( 9, 'B 3003 RTC', 3, 40, 'active', 'Irwan Saputra'),
    -- Route 4
    (10, 'B 4001 RTD', 4, 40, 'active', 'Joko Susilo'),
    (11, 'B 4002 RTD', 4, 40, 'active', 'Kurniawan Adi'),
    (12, 'B 4003 RTD', 4, 40, 'active', 'Lutfi Hakim'),
    -- Route 5
    (13, 'B 5001 RTE', 5, 40, 'active', 'Muhammad Rizki'),
    (14, 'B 5002 RTE', 5, 40, 'active', 'Nugroho Wibowo'),
    (15, 'B 5003 RTE', 5, 40, 'active', 'Oki Pratama'),
    -- Route 6
    (16, 'B 6001 RTF', 6, 40, 'active', 'Pandu Kristianto'),
    (17, 'B 6002 RTF', 6, 40, 'active', 'Qodir Mansyur'),
    (18, 'B 6003 RTF', 6, 40, 'active', 'Rendi Firmansyah'),
    -- Route 7
    (19, 'B 7001 RTG', 7, 40, 'active', 'Surya Dharma'),
    (20, 'B 7002 RTG', 7, 40, 'active', 'Teguh Prasetyo'),
    (21, 'B 7003 RTG', 7, 40, 'active', 'Umar Bahari'),
    -- Route 8
    (22, 'B 8001 RTH', 8, 40, 'active', 'Vicky Nugraha'),
    (23, 'B 8002 RTH', 8, 40, 'active', 'Wahyu Sanjaya'),
    (24, 'B 8003 RTH', 8, 40, 'active', 'Xaverius Anto'),
    -- Route 9
    (25, 'B 9001 RTI', 9, 40, 'active', 'Yusuf Halim'),
    (26, 'B 9002 RTI', 9, 40, 'active', 'Zainal Abidin'),
    (27, 'B 9003 RTI', 9, 40, 'active', 'Arif Budiman'),
    -- Route 10
    (28, 'B 0001 RTJ', 10, 40, 'active', 'Bagas Permana'),
    (29, 'B 0002 RTJ', 10, 40, 'active', 'Candra Lesmana'),
    (30, 'B 0003 RTJ', 10, 40, 'active', 'Dimas Prasetya');
