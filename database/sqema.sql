
CREATE TABLE IF NOT EXISTS passenger_passengers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    card_number VARCHAR(50) UNIQUE,
    balance DECIMAL(10,2) DEFAULT 0,
    zone_id INT,
    role ENUM('passenger', 'admin') DEFAULT 'passenger',
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_zone_id (zone_id),
    INDEX idx_email (email)
);

CREATE TABLE IF NOT EXISTS passenger_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    passenger_id INT NOT NULL,
    route_id INT NOT NULL,
    origin_stop_id INT NOT NULL,
    dest_stop_id INT NOT NULL,
    status ENUM('active', 'used', 'cancelled') DEFAULT 'active',
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_passenger_id (passenger_id),
    INDEX idx_route_id (route_id),
    INDEX idx_created_at (created_at)
);

CREATE TABLE IF NOT EXISTS passenger_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    passenger_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    type ENUM('delay', 'anomaly', 'ticket', 'general') DEFAULT 'general',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_passenger_id (passenger_id),
    INDEX idx_created_at (created_at)
);



CREATE TABLE IF NOT EXISTS stop_stops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    route_id INT NOT NULL,
    lat DECIMAL(10,7) NOT NULL,
    lng DECIMAL(10,7) NOT NULL,
    zone_id INT,
    sequence_order INT NOT NULL,
    INDEX idx_route_id (route_id),
    INDEX idx_zone_id (zone_id)
);

CREATE TABLE IF NOT EXISTS stop_passenger_counts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stop_id INT NOT NULL,
    bus_id INT NULL,
    boarded INT DEFAULT 0,
    alighted INT DEFAULT 0,
    current_load INT DEFAULT 0,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_stop_id (stop_id),
    INDEX idx_recorded_at (recorded_at)
);

CREATE TABLE IF NOT EXISTS stop_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stop_id INT NOT NULL,
    alert_type ENUM('crowded', 'anomaly', 'delay', 'general') DEFAULT 'general',
    severity ENUM('low', 'medium', 'high') DEFAULT 'medium',
    message TEXT NOT NULL,
    threshold INT,
    resolved_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_stop_id (stop_id)
);


CREATE TABLE IF NOT EXISTS oauth_clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id VARCHAR(80) NOT NULL,
    client_secret VARCHAR(80) NOT NULL,
    redirect_uri VARCHAR(255) NOT NULL,
    grant_types VARCHAR(80) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS oauth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id VARCHAR(80) NOT NULL,
    user_id INT NULL,
    access_token VARCHAR(255) NOT NULL,
    access_token_expires_at DATETIME NOT NULL,
    refresh_token VARCHAR(255) NULL,
    refresh_token_expires_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'passenger'
);

-- =============================================================================
-- FLEET SERVICE TABLES
-- =============================================================================

CREATE TABLE IF NOT EXISTS fleet_routes (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    origin        VARCHAR(100) NOT NULL,
    destination   VARCHAR(100) NOT NULL,
    total_stops   INT          NOT NULL DEFAULT 0,
    distance_km   DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    est_duration_min INT       NOT NULL DEFAULT 0,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fleet_buses (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    plate_number  VARCHAR(20)  NOT NULL UNIQUE,
    route_id      INT          NULL,
    capacity      INT          NOT NULL DEFAULT 40,
    status        ENUM('active','inactive','maintenance','incident') NOT NULL DEFAULT 'active',
    driver_name   VARCHAR(100) NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fleet_buses_route_id (route_id),
    INDEX idx_fleet_buses_status   (status),
    CONSTRAINT fk_fleet_buses_route FOREIGN KEY (route_id) REFERENCES fleet_routes(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fleet_gps_logs (
    id               BIGINT AUTO_INCREMENT PRIMARY KEY,
    bus_id           INT           NOT NULL,
    route_id         INT           NOT NULL,
    lat              DECIMAL(10,7) NOT NULL,
    lng              DECIMAL(10,7) NOT NULL,
    speed_kmh        DECIMAL(6,2)  NOT NULL DEFAULT 0.00,
    heading          DECIMAL(6,2)  NOT NULL DEFAULT 0.00,
    passenger_count  INT           NOT NULL DEFAULT 0,
    engine_temp      DECIMAL(5,2)  NULL,
    recorded_at      DATETIME      NOT NULL,
    INDEX idx_fleet_gps_bus_id      (bus_id),
    INDEX idx_fleet_gps_route_id    (route_id),
    INDEX idx_fleet_gps_recorded_at (recorded_at),
    CONSTRAINT fk_fleet_gps_bus   FOREIGN KEY (bus_id)   REFERENCES fleet_buses(id)   ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_fleet_gps_route FOREIGN KEY (route_id) REFERENCES fleet_routes(id)  ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS fleet_incidents (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    bus_id      INT  NOT NULL,
    type        ENUM('breakdown','accident','traffic','maintenance','anomaly','other') NOT NULL DEFAULT 'other',
    severity    ENUM('low','medium','high','critical') NOT NULL DEFAULT 'low',
    description TEXT,
    resolved_at DATETIME NULL,
    reported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fleet_incidents_bus_id      (bus_id),
    INDEX idx_fleet_incidents_severity    (severity),
    INDEX idx_fleet_incidents_reported_at (reported_at),
    CONSTRAINT fk_fleet_incidents_bus FOREIGN KEY (bus_id) REFERENCES fleet_buses(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
