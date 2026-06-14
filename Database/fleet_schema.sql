-- =============================================================
-- Fleet Service Schema
-- Prefix: fleet_
-- =============================================================

-- -------------------------------------------------------------
-- 1. fleet_routes
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fleet_routes` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`             VARCHAR(100) NOT NULL,
    `origin`           VARCHAR(100) NOT NULL,
    `destination`      VARCHAR(100) NOT NULL,
    `total_stops`      INT UNSIGNED DEFAULT 0,
    `distance_km`      DECIMAL(8,2) DEFAULT NULL,
    `est_duration_min` INT UNSIGNED DEFAULT NULL,
    `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------------------------
-- 2. fleet_buses
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fleet_buses` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `plate_number` VARCHAR(20)  NOT NULL UNIQUE,
    `route_id`     INT UNSIGNED DEFAULT NULL,
    `capacity`     INT UNSIGNED NOT NULL DEFAULT 40,
    `status`       ENUM('active','inactive','maintenance','incident') NOT NULL DEFAULT 'active',
    `driver_name`  VARCHAR(100) DEFAULT NULL,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_route_id` (`route_id`),
    INDEX `idx_status`   (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------------------------
-- 3. fleet_gps_logs
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fleet_gps_logs` (
    `id`              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `bus_id`          INT UNSIGNED   NOT NULL,
    `route_id`        INT UNSIGNED   NOT NULL,
    `lat`             DECIMAL(10,7)  NOT NULL,
    `lng`             DECIMAL(10,7)  NOT NULL,
    `speed_kmh`       DECIMAL(6,2)   NOT NULL DEFAULT 0,
    `heading`         DECIMAL(6,2)   NOT NULL DEFAULT 0,
    `passenger_count` INT UNSIGNED   NOT NULL DEFAULT 0,
    `engine_temp`     DECIMAL(5,2)   DEFAULT NULL,
    `recorded_at`     DATETIME       NOT NULL,

    INDEX `idx_gps_bus_id`     (`bus_id`),
    INDEX `idx_gps_route_id`   (`route_id`),
    INDEX `idx_gps_recorded_at`(`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -------------------------------------------------------------
-- 4. fleet_incidents
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fleet_incidents` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `bus_id`      INT UNSIGNED NOT NULL,
    `type`        ENUM('breakdown','accident','traffic','maintenance','anomaly','other') NOT NULL DEFAULT 'other',
    `severity`    ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    `description` TEXT NOT NULL,
    `resolved_at` DATETIME DEFAULT NULL,
    `reported_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_incident_bus_id`    (`bus_id`),
    INDEX `idx_incident_severity`  (`severity`),
    INDEX `idx_incident_reported_at`(`reported_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
