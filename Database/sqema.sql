
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
    bus_id INT NOT NULL,
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