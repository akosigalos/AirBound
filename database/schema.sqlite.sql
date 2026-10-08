PRAGMA foreign_keys = ON;

CREATE TABLE users (
  id INTEGER PRIMARY KEY,
  name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  created_at TEXT NOT NULL
);

CREATE TABLE devices (
  id INTEGER PRIMARY KEY,
  name TEXT NOT NULL,
  api_key TEXT NOT NULL UNIQUE,
  lat REAL DEFAULT NULL,
  lng REAL DEFAULT NULL,
  last_seen INTEGER DEFAULT NULL,
  status TEXT NOT NULL DEFAULT 'offline' CHECK (status IN ('online', 'offline')),
  created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE readings (
  id INTEGER PRIMARY KEY,
  device_id INTEGER NOT NULL,
  pm25 REAL DEFAULT NULL,
  pm10 REAL DEFAULT NULL,
  co REAL DEFAULT NULL,
  no2 REAL DEFAULT NULL,
  raw TEXT DEFAULT NULL,
  created_at INTEGER NOT NULL,
  FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
);
CREATE INDEX idx_readings_device_id ON readings(device_id);

CREATE TABLE alerts (
  id INTEGER PRIMARY KEY,
  device_id INTEGER NOT NULL,
  reading_id INTEGER DEFAULT NULL,
  pollutant TEXT DEFAULT NULL,
  value REAL DEFAULT NULL,
  unit TEXT DEFAULT NULL,
  level TEXT NOT NULL,
  icon TEXT DEFAULT NULL,
  color TEXT DEFAULT NULL,
  message TEXT DEFAULT NULL,
  lat REAL DEFAULT NULL,
  lng REAL DEFAULT NULL,
  created_at INTEGER NOT NULL,
  read_at INTEGER DEFAULT NULL,
  resolved_at INTEGER DEFAULT NULL,
  FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE,
  FOREIGN KEY (reading_id) REFERENCES readings(id) ON DELETE SET NULL
);
CREATE INDEX idx_alerts_created_at ON alerts(created_at);
CREATE INDEX idx_alerts_device_id ON alerts(device_id);
CREATE INDEX idx_alerts_pollutant ON alerts(pollutant);

CREATE TABLE sensor_data (
  id INTEGER PRIMARY KEY,
  mq2 REAL DEFAULT NULL,
  mq135 REAL DEFAULT NULL,
  dust REAL DEFAULT NULL,
  created_at INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX idx_sensor_data_created_at ON sensor_data(created_at);

CREATE TABLE predictions (
  id INTEGER PRIMARY KEY,
  predicted_aqi TEXT DEFAULT NULL,
  confidence REAL DEFAULT NULL,
  prediction_time TEXT DEFAULT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
