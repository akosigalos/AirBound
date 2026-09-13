-- Creates tables to store devices and sensor readings
CREATE TABLE IF NOT EXISTS `devices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(160) NOT NULL,
  `api_key` VARCHAR(64) NOT NULL UNIQUE,
  `lat` DECIMAL(10,6) DEFAULT NULL,
  `lng` DECIMAL(10,6) DEFAULT NULL,
  `last_seen` INT UNSIGNED DEFAULT NULL,
  `status` ENUM('online','offline') DEFAULT 'offline',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `readings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `device_id` INT UNSIGNED NOT NULL,
  `pm25` DECIMAL(8,3) DEFAULT NULL,
  `pm10` DECIMAL(8,3) DEFAULT NULL,
  `co` DECIMAL(10,4) DEFAULT NULL, -- ppm
  `no2` DECIMAL(8,3) DEFAULT NULL, -- ppb
  `raw` JSON DEFAULT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX (`device_id`),
  CONSTRAINT `fk_readings_device` FOREIGN KEY (`device_id`) REFERENCES `devices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Example: insert a device (replace api_key before using in production)
-- INSERT INTO devices (name, api_key) VALUES ('Sensor-A', 'replace-with-secure-key');
