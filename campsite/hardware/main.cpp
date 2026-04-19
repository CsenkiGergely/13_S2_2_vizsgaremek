/*********
  ESP32-S3 QR Scanner - Laravel backend feldolgozással
  Board: Freenove ESP32-S3 WROOM (N16R8)
*********/

#include <Arduino.h>
#include <WiFi.h>
#include <WiFiManager.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <Preferences.h>
#include "esp_camera.h"

// ---- Laravel konfig ----
#define LARAVEL_URL "https://campsite.hu/api/bookings/scan-image"
// ------------------------

// ---- Fix dev adatok ----
#define FIXED_WIFI_SSID     "Telekom-328614"
#define FIXED_WIFI_PASSWORD "61493047084321806396"
#define FIXED_CAMP_ID       "50"
#define FIXED_AUTH_TOKEN    "xBwuh3cbYmYAjQT1"
// ------------------------

#define SCAN_INTERVAL_MS    5000

Preferences prefs;
char auth_token[128] = "";
char camp_id[32] = "";

#define CAM_PIN_PWDN   -1
#define CAM_PIN_RESET  -1
#define CAM_PIN_XCLK   15
#define CAM_PIN_SIOD   4
#define CAM_PIN_SIOC   5
#define CAM_PIN_D9     16
#define CAM_PIN_D8     17
#define CAM_PIN_D7     18
#define CAM_PIN_D6     12
#define CAM_PIN_D5     10
#define CAM_PIN_D4     8
#define CAM_PIN_D3     9
#define CAM_PIN_D2     11
#define CAM_PIN_VSYNC  6
#define CAM_PIN_HREF   7
#define CAM_PIN_PCLK   13

unsigned long lastScanTime = 0;

bool sendToLaravel(camera_fb_t *fb) {
  if (!fb || !fb->buf || fb->len == 0) {
    return false;
  }

  WiFiClientSecure client;
  client.setInsecure();

  HTTPClient http;
  http.setConnectTimeout(12000);
  http.setTimeout(30000);
  http.useHTTP10(true);
  http.setReuse(false);

  if (!http.begin(client, LARAVEL_URL)) {
    Serial.println("HTTP begin hiba");
    return false;
  }

  http.addHeader("Content-Type", "image/jpeg");
  http.addHeader("Authorization", "Bearer " + String(auth_token));
  http.addHeader("Accept", "application/json");
  http.addHeader("X-Camp-ID", String(camp_id));

  const unsigned long start = millis();
  int httpCode = http.POST(fb->buf, fb->len);
  const unsigned long elapsed = millis() - start;

  if (httpCode == 200) {
    String response = http.getString();
    Serial.printf("HTTP 200 (%lu ms)\n", elapsed);

    if (response.indexOf("\"valid\":true") >= 0) {
      int msgStart = response.indexOf("\"message\":\"");
      if (msgStart >= 0) {
        msgStart += 11;
        int msgEnd = response.indexOf('"', msgStart);
        String msg = response.substring(msgStart, msgEnd);
        Serial.println("✓ SIKERES: " + msg);
      } else {
        Serial.println("bejelentkezes");
      }
      digitalWrite(48, HIGH);
      delay(1000);
      digitalWrite(48, LOW);
      http.end();
      return true;
    }

    int msgStart = response.indexOf("\"message\":\"");
    if (msgStart >= 0) {
      msgStart += 11;
      int msgEnd = response.indexOf('"', msgStart);
      String msg = response.substring(msgStart, msgEnd);
      Serial.println("✗ ERVENYTELEN: " + msg);
    } else {
      Serial.println("QR kod");
    }
  } else if (httpCode == 422) {
    Serial.println("Nem talalt QR kodot a kepen");
  } else if (httpCode == 401) {
    Serial.println("Token hiba");
  } else if (httpCode == 503 || httpCode == 502 || httpCode == 504) {
    Serial.printf("HTTP hiba: %d (%lu ms)\n", httpCode, elapsed);
    String response = http.getString();
    if (response.length() > 0) {
      Serial.println("Valasz: " + response);
    }
    Serial.println("Szerver ideiglenesen nem elerheto");
  } else {
    Serial.printf("HTTP hiba: %d (%lu ms)\n", httpCode, elapsed);
  }

  http.end();
  return false;
}

void setup() {
  Serial.begin(115200);

  pinMode(48, OUTPUT);
  digitalWrite(48, LOW);

  strncpy(auth_token, FIXED_AUTH_TOKEN, sizeof(auth_token));
  strncpy(camp_id, FIXED_CAMP_ID, sizeof(camp_id));
  auth_token[sizeof(auth_token) - 1] = '\0';
  camp_id[sizeof(camp_id) - 1] = '\0';

  camera_config_t config;
  config.ledc_channel = LEDC_CHANNEL_0;
  config.ledc_timer   = LEDC_TIMER_0;
  config.pin_d0       = CAM_PIN_D2;
  config.pin_d1       = CAM_PIN_D3;
  config.pin_d2       = CAM_PIN_D4;
  config.pin_d3       = CAM_PIN_D5;
  config.pin_d4       = CAM_PIN_D6;
  config.pin_d5       = CAM_PIN_D7;
  config.pin_d6       = CAM_PIN_D8;
  config.pin_d7       = CAM_PIN_D9;
  config.pin_xclk     = CAM_PIN_XCLK;
  config.pin_pclk     = CAM_PIN_PCLK;
  config.pin_vsync    = CAM_PIN_VSYNC;
  config.pin_href     = CAM_PIN_HREF;
  config.pin_sccb_sda = CAM_PIN_SIOD;
  config.pin_sccb_scl = CAM_PIN_SIOC;
  config.pin_pwdn     = CAM_PIN_PWDN;
  config.pin_reset    = CAM_PIN_RESET;
  config.xclk_freq_hz = 20000000;
  config.pixel_format = PIXFORMAT_JPEG;
  config.frame_size   = FRAMESIZE_QVGA;
  config.jpeg_quality = 14;
  config.fb_count     = 3;
  config.fb_location  = CAMERA_FB_IN_PSRAM;
  config.grab_mode    = CAMERA_GRAB_LATEST;

  if (esp_camera_init(&config) != ESP_OK) {
    Serial.println("Kamera hiba");
    return;
  }

  ledcDetachPin(48);
  digitalWrite(48, LOW);

  WiFi.mode(WIFI_STA);
  WiFi.setAutoReconnect(true);
  WiFi.begin(FIXED_WIFI_SSID, FIXED_WIFI_PASSWORD);

  Serial.print("WiFi csatlakozas");
  const unsigned long wifiStart = millis();
  while (WiFi.status() != WL_CONNECTED && (millis() - wifiStart) < 15000) {
    delay(300);
    Serial.print(".");
  }
  Serial.println();

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi kapcsolodas sikertelen, captive portal indul...");
    WiFiManager wm;
    wm.setConfigPortalTimeout(0);
    wm.setCaptivePortalEnable(true);
    wm.setAPClientCheck(true);
    wm.setWebPortalClientCheck(true);
    if (!wm.startConfigPortal("CampSite-Setup", "12345678")) {
      Serial.println("WiFi portal sikertelen");
      ESP.restart();
    }
  }

  prefs.begin("config", false);
  prefs.putString("token", auth_token);
  prefs.putString("campid", camp_id);
  prefs.end();

  Serial.print("IP: ");
  Serial.println(WiFi.localIP());
  Serial.print("Token: [");
  Serial.print(auth_token);
  Serial.println("]");
  Serial.print("Camp ID: [");
  Serial.print(camp_id);
  Serial.println("]");
  Serial.println("Szkennelés indult...");
}

void loop() {
  unsigned long now = millis();

  if (WiFi.status() != WL_CONNECTED) {
    WiFi.reconnect();
    delay(300);
    return;
  }

  if (now - lastScanTime >= SCAN_INTERVAL_MS) {
    lastScanTime = now;
    camera_fb_t *fb = esp_camera_fb_get();
    if (fb) {
      sendToLaravel(fb);
      esp_camera_fb_return(fb);
    }
  }
}