CREATE DATABASE IF NOT EXISTS donornexa
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE donornexa;

SET NAMES utf8mb4;
SET time_zone = '+05:00';

CREATE TABLE IF NOT EXISTS cities (
    id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    city_name VARCHAR(80) NOT NULL,
    province VARCHAR(80) NOT NULL,
    UNIQUE KEY uq_cities_name_province (city_name, province)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(16) NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role_status (role, status),
    CONSTRAINT chk_users_role CHECK (role IN ('admin', 'hospital', 'donor')),
    CONSTRAINT chk_users_status CHECK (status IN ('active', 'blocked', 'pending'))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS donors (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    blood_group VARCHAR(3) NOT NULL,
    dob DATE NOT NULL,
    weight DECIMAL(5,2) NOT NULL,
    city_id SMALLINT UNSIGNED NOT NULL,
    phone VARCHAR(20) NOT NULL,
    cnic CHAR(15) NOT NULL,
    last_donation_date DATE NULL,
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    photo VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_donors_cnic (cnic),
    KEY idx_donors_match (blood_group, city_id, is_available),
    KEY idx_donors_last_donation (last_donation_date),
    CONSTRAINT fk_donors_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_donors_city FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE RESTRICT,
    CONSTRAINT chk_donors_blood_group CHECK (blood_group IN ('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-')),
    CONSTRAINT chk_donors_weight CHECK (weight >= 0),
    CONSTRAINT chk_donors_available CHECK (is_available IN (0, 1))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS hospitals (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    hospital_name VARCHAR(160) NOT NULL,
    licence_no VARCHAR(80) NOT NULL,
    licence_file VARCHAR(255) NULL,
    city_id SMALLINT UNSIGNED NOT NULL,
    contact_phone VARCHAR(20) NOT NULL,
    verification_status VARCHAR(16) NOT NULL DEFAULT 'pending',
    trust_score SMALLINT NOT NULL DEFAULT 100,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_hospitals_licence (licence_no),
    KEY idx_hospitals_city_verification (city_id, verification_status),
    CONSTRAINT fk_hospitals_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_hospitals_city FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE RESTRICT,
    CONSTRAINT chk_hospitals_verification CHECK (verification_status IN ('pending', 'verified', 'rejected')),
    CONSTRAINT chk_hospitals_trust CHECK (trust_score BETWEEN 0 AND 100)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(60) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS remember_tokens (
    token_hash CHAR(64) PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_remember_user_expiry (user_id, expires_at),
    CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS blood_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hospital_id BIGINT UNSIGNED NOT NULL,
    blood_group VARCHAR(3) NOT NULL,
    units_needed TINYINT UNSIGNED NOT NULL,
    units_reserved TINYINT UNSIGNED NOT NULL DEFAULT 0,
    units_fulfilled TINYINT UNSIGNED NOT NULL DEFAULT 0,
    urgency VARCHAR(12) NOT NULL,
    required_by DATETIME NOT NULL,
    ward VARCHAR(100) NOT NULL,
    notes VARCHAR(1000) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_requests_public (status, urgency, required_by),
    KEY idx_requests_hospital_status (hospital_id, status, created_at),
    KEY idx_requests_blood_city (blood_group, urgency, required_by),
    CONSTRAINT fk_requests_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(user_id) ON DELETE RESTRICT,
    CONSTRAINT chk_requests_blood_group CHECK (blood_group IN ('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-')),
    CONSTRAINT chk_requests_units CHECK (units_needed BETWEEN 1 AND 20),
    CONSTRAINT chk_requests_counts CHECK (
        units_reserved + units_fulfilled <= units_needed
    ),
    CONSTRAINT chk_requests_urgency CHECK (urgency IN ('critical', 'urgent', 'normal')),
    CONSTRAINT chk_requests_status CHECK (status IN ('open', 'partially_fulfilled', 'fulfilled', 'cancelled', 'expired'))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS request_matches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id BIGINT UNSIGNED NOT NULL,
    donor_id BIGINT UNSIGNED NOT NULL,
    score DECIMAL(6,2) NOT NULL DEFAULT 0,
    status VARCHAR(16) NOT NULL DEFAULT 'notified',
    decline_reason VARCHAR(240) NULL,
    responded_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_match_request_donor (request_id, donor_id),
    KEY idx_match_donor_status (donor_id, status, created_at),
    KEY idx_match_request_status (request_id, status),
    CONSTRAINT fk_matches_request FOREIGN KEY (request_id) REFERENCES blood_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_matches_donor FOREIGN KEY (donor_id) REFERENCES donors(user_id) ON DELETE CASCADE,
    CONSTRAINT chk_matches_status CHECK (status IN ('notified', 'accepted', 'declined', 'no_response', 'donated', 'cancelled'))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS donations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    match_id BIGINT UNSIGNED NOT NULL,
    units TINYINT UNSIGNED NOT NULL DEFAULT 1,
    donated_on DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    verified_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_donation_match (match_id),
    KEY idx_donations_date (donated_on),
    CONSTRAINT fk_donations_match FOREIGN KEY (match_id) REFERENCES request_matches(id) ON DELETE RESTRICT,
    CONSTRAINT fk_donations_verified_by FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT chk_donations_units CHECK (units BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS blood_inventory (
    hospital_id BIGINT UNSIGNED NOT NULL,
    blood_group VARCHAR(3) NOT NULL,
    units_available SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (hospital_id, blood_group),
    CONSTRAINT fk_inventory_hospital FOREIGN KEY (hospital_id) REFERENCES hospitals(user_id) ON DELETE CASCADE,
    CONSTRAINT chk_inventory_blood_group CHECK (blood_group IN ('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    message VARCHAR(500) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notifications_user_read (user_id, is_read, created_at),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    entity VARCHAR(60) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    old_value JSON NULL,
    new_value JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_created (created_at),
    KEY idx_audit_user_action (user_id, action, created_at),
    KEY idx_audit_entity (entity, entity_id, created_at),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO system_settings (setting_key, setting_value) VALUES
    ('cooldown_days', '90'),
    ('match_top_n', '20')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

CREATE OR REPLACE VIEW v_eligible_donors AS
SELECT
    u.id AS donor_id,
    u.name,
    u.email,
    u.status,
    d.blood_group,
    d.dob,
    TIMESTAMPDIFF(YEAR, d.dob, CURDATE()) AS age,
    d.weight,
    d.city_id,
    c.city_name,
    d.phone,
    d.last_donation_date,
    d.is_available,
    d.photo
FROM users u
JOIN donors d ON d.user_id = u.id
JOIN cities c ON c.id = d.city_id
JOIN system_settings cooldown ON cooldown.setting_key = 'cooldown_days'
WHERE u.role = 'donor'
  AND u.status = 'active'
  AND d.is_available = 1
  AND TIMESTAMPDIFF(YEAR, d.dob, CURDATE()) BETWEEN 18 AND 60
  AND d.weight >= 50
  AND (
      d.last_donation_date IS NULL
      OR DATE_ADD(d.last_donation_date, INTERVAL CAST(cooldown.setting_value AS UNSIGNED) DAY) <= CURDATE()
  )
  AND NOT EXISTS (
      SELECT 1
      FROM request_matches active_match
      JOIN blood_requests active_request ON active_request.id = active_match.request_id
      WHERE active_match.donor_id = u.id
        AND active_match.status = 'accepted'
        AND active_request.status IN ('open', 'partially_fulfilled')
  );

DROP TRIGGER IF EXISTS trg_donation_updates_donor;
DELIMITER //
CREATE TRIGGER trg_donation_updates_donor
AFTER INSERT ON donations
FOR EACH ROW
BEGIN
    UPDATE donors d
    JOIN request_matches m ON m.donor_id = d.user_id
    SET d.last_donation_date = DATE(NEW.donated_on),
        d.is_available = 0
    WHERE m.id = NEW.match_id;
END//
DELIMITER ;
