# AIR-BOUND — Minimal UI + Auth (Tailwind + PHP / MySQL)

This workspace contains a minimal AIR-BOUND demo with:
- Landing page (`index.php`)
- Sign Up (`signup.php`) and Login (`login.php`)
- Protected Dashboard (`dashboard.php`)
- PHP API endpoints in `api/` for register, login, logout, and simulated data
- Database schema: `db.sql`

Setup (XAMPP):

1. Start Apache + MySQL in XAMPP.
2. Create the DB and table:

```sql
-- in phpMyAdmin or mysql CLI run the contents of db.sql
```

3. Edit `api/config.php` if your DB credentials are different (default expects `airbound` DB, user `root`, no password).
4. Visit `http://localhost/Iot/` to view the landing page.
5. Use Sign Up -> then Login -> Dashboard. The dashboard uses session-based protection.

Notes:
- Passwords are stored using `password_hash()`.
- This is a demo scaffold — please harden sessions, add CSRF protection and HTTPS in production.

ESP32 air-quality integration

1. Create one device record in the database and copy its `api_key`.
2. Point the ESP32 to `http://YOUR_PC_IP/Iot/api/ingest.php`.
3. Send JSON with `pm25`, `pm10`, optional `lat`, and `lng`.
4. Print the same values to Serial Monitor.
5. If a value is above the threshold, print an alert on Serial Monitor and let the server create a dashboard alert.

Example ESP32 flow:

```cpp
#include <WiFi.h>
#include <HTTPClient.h>

const char* ssid = "YOUR_WIFI";
const char* password = "YOUR_WIFI_PASSWORD";
const char* serverUrl = "http://192.168.1.10/Iot/api/ingest.php";
const char* apiKey = "YOUR_DEVICE_API_KEY";

float readPm25() {
	return 72.5; // replace with your sensor reading
}

float readPm10() {
	return 164.8; // replace with your sensor reading
}

void sendAlertIfNeeded(float pm25, float pm10) {
	if (pm25 > 55 || pm10 > 150) {
		Serial.println("ALERT: Air quality exceeded safe threshold");
	}
}

void setup() {
	Serial.begin(115200);
	WiFi.begin(ssid, password);
	while (WiFi.status() != WL_CONNECTED) {
		delay(500);
		Serial.println("Connecting to WiFi...");
	}
	Serial.println("WiFi connected");
}

void loop() {
	if (WiFi.status() == WL_CONNECTED) {
		float pm25 = readPm25();
		float pm10 = readPm10();

		Serial.print("PM2.5: "); Serial.println(pm25);
		Serial.print("PM10: "); Serial.println(pm10);
		sendAlertIfNeeded(pm25, pm10);

		HTTPClient http;
		http.begin(serverUrl);
		http.addHeader("Content-Type", "application/json");
		http.addHeader("X-API-KEY", apiKey);

		String body = "{";
		body += "\"pm25\":" + String(pm25, 1) + ",";
		body += "\"pm10\":" + String(pm10, 1);
		body += "}";

		int code = http.POST(body);
		Serial.print("HTTP code: ");
		Serial.println(code);
		http.end();
	}

	delay(5000);
}
```

Temporary data for testing:
- Open the admin monitoring page.
- Click `Load Temporary Data`.
- That inserts a sample PM2.5 / PM10 reading into `readings`.
- The same sample appears in analytics and can create an alert when above threshold.
