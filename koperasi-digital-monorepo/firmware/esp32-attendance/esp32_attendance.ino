#include <ArduinoJson.h>
#include <HTTPClient.h>
#include <WiFi.h>

const char* WIFI_SSID = "YOUR_WIFI";
const char* WIFI_PASSWORD = "YOUR_PASSWORD";
const char* API_URL = "http://192.168.1.10:4000/api/attendance/iot/logs";
const char* IOT_API_KEY = "change-this-device-api-key";
const char* DEVICE_CODE = "ESP32-KANTOR-01";

void setup() {
  Serial.begin(115200);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("WiFi connected");
}

void loop() {
  int fingerprintTemplateId = readFingerprintTemplateId();

  if (fingerprintTemplateId > 0) {
    sendAttendanceLog(fingerprintTemplateId);
    delay(3000);
  }

  delay(250);
}

int readFingerprintTemplateId() {
  return 0;
}

void sendAttendanceLog(int fingerprintTemplateId) {
  if (WiFi.status() != WL_CONNECTED) {
    return;
  }

  HTTPClient http;
  StaticJsonDocument<256> payload;
  String body;

  payload["deviceCode"] = DEVICE_CODE;
  payload["fingerprintTemplateId"] = "FP-" + String(fingerprintTemplateId);
  payload["employeeId"] = "lookup-on-backend";
  payload["occurredAt"] = "use-rtc-or-backend-receivedAt";

  serializeJson(payload, body);

  http.begin(API_URL);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-IOT-API-KEY", IOT_API_KEY);

  int statusCode = http.POST(body);
  Serial.printf("Attendance POST status: %d\n", statusCode);
  http.end();
}
