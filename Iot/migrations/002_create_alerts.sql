-- Migration: create alerts table

CREATE TABLE IF NOT EXISTS alerts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  device_id INT NOT NULL,
  reading_id INT DEFAULT NULL,
  pollutant VARCHAR(16) DEFAULT NULL,
  value DOUBLE DEFAULT NULL,
  unit VARCHAR(16) DEFAULT NULL,
  level VARCHAR(32) NOT NULL,
  icon VARCHAR(8) DEFAULT NULL,
  color VARCHAR(32) DEFAULT NULL,
  message TEXT,
  lat DOUBLE DEFAULT NULL,
  lng DOUBLE DEFAULT NULL,
  created_at INT NOT NULL,
  read_at INT DEFAULT NULL,
  resolved_at INT DEFAULT NULL,
  FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
  INDEX (created_at),
  INDEX (device_id)
  ,INDEX (pollutant)
);
