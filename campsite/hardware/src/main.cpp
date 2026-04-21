/*********
  ESP32-S3 QR Scanner
  Freenove ESP32-S3 WROOM (N16R8)
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

#define SCAN_INTERVAL_MS    1500
#define CONFIG_PORTAL_TIMEOUT_SEC 45

Preferences prefs;
char auth_token[128] = "";
char camp_id[32] = "";
bool shouldSaveConfig = false;

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

// Jelzi, hogy a captive portalon a felhasználó mentett változást.
void onSaveConfigCallback() {
  shouldSaveConfig = true;
}

// sikeres/sikertelen állapot.
void printScanResult(bool success, unsigned long elapsedMs) {
  const char *label = success ? "SIKERES" : "SIKERTELEN";
  Serial.printf("%s (%lu ms)\n", label, elapsedMs);
}

// WiFiManager portal
void configurePortal(WiFiManager &wm, WiFiManagerParameter &tokenParam) {
  const char *wm_menu[] = {"wifi", "exit"};
  wm.setMenu(wm_menu, 2);
  wm.setTitle("CampSite QR Scanner");
  wm.setCustomHeadElement(
    "<meta charset='UTF-8'>"
    "<style>"
    "body{background:#f5f7f0;font-family:'Segoe UI',Arial,sans-serif;}"
    "h1,h3{color:#516E37;}"
    ".wrap{max-width:400px;margin:0 auto;padding:20px;}"
    "button,.btn{background:#DF8534!important;border:none!important;border-radius:8px!important;color:#fff!important;"
    "font-size:16px!important;padding:12px!important;cursor:pointer;transition:background .2s;}"
    "button:hover,.btn:hover{background:#c97229!important;}"
    "input[type='text'],input[type='password'],select{border:2px solid #ddd!important;border-radius:8px!important;"
    "padding:10px!important;font-size:14px!important;width:100%!important;box-sizing:border-box!important;"
    "transition:border .2s;}"
    "input:focus{border-color:#516E37!important;outline:none!important;}"
    "a{color:#DF8534;text-decoration:none;}"
    "a:hover{color:#516E37;}"
    "</style>"
    "<script>"
    "window.onload=function(){"
    "var b=document.querySelectorAll('button');"
    "for(var i=0;i<b.length;i++){"
    "var t=b[i].textContent.trim();"
    "if(t=='Configure WiFi')b[i].innerHTML='WiFi beállítások';"
    "else if(t=='Exit')b[i].innerHTML='Kilépés';"
    "else if(t=='Save')b[i].innerHTML='Mentés';"
    "}"
    "var l=document.querySelectorAll('label');"
    "for(var j=0;j<l.length;j++){"
    "if(l[j].textContent.trim()=='Password')l[j].innerHTML='Jelszó';"
    "if(l[j].textContent.trim()=='Show Password')l[j].innerHTML=' Jelszó megjelenítése';"
    "}"
    "};"
    "</script>"
  );

  wm.setSaveConfigCallback(onSaveConfigCallback);
  wm.addParameter(&tokenParam);
  wm.setConfigPortalTimeout(CONFIG_PORTAL_TIMEOUT_SEC);
  wm.setCaptivePortalEnable(true);
  wm.setAPClientCheck(true);
  wm.setWebPortalClientCheck(true);
}

// Laravelnek küldi a JPEG képet feldolgozásra
bool sendToLaravel(camera_fb_t *fb) {
  if (!fb || !fb->buf || fb->len == 0) {
    return false;
  }

  WiFiClientSecure client;
  client.setInsecure();

  HTTPClient http;
  http.setConnectTimeout(8000);
  http.setTimeout(15000);
  http.useHTTP10(false);
  http.setReuse(true);

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

    if (response.indexOf("\"valid\":true") >= 0) {
      printScanResult(true, elapsed);
      digitalWrite(48, HIGH);
      delay(1000);
      digitalWrite(48, LOW);
      http.end();
      return true;
    }

    printScanResult(false, elapsed);
  } else if (httpCode == 422) {
    printScanResult(false, elapsed);
  } else if (httpCode == 401) {
    printScanResult(false, elapsed);
  } else if (httpCode == 503 || httpCode == 502 || httpCode == 504) {
    printScanResult(false, elapsed);
  } else {
    printScanResult(false, elapsed);
  }

  http.end();
  return false;
}

void setup() {
  Serial.begin(115200);

  pinMode(48, OUTPUT);
  digitalWrite(48, LOW);

  prefs.begin("config", false);
  String savedToken = prefs.getString("token", "");
  String savedCampId = prefs.getString("campid", "");

  strncpy(auth_token, savedToken.c_str(), sizeof(auth_token));
  strncpy(camp_id, savedCampId.c_str(), sizeof(camp_id));
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
  config.frame_size   = FRAMESIZE_VGA;
  config.jpeg_quality = 10;
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

  // Minden indításnál rövid ideig legyen elérhető portal beállítás módosításhoz.
  WiFiManager wm;
  WiFiManagerParameter tokenParam("token", "Bearer Token", auth_token, sizeof(auth_token) - 1,
                                  "type='password' placeholder='pl. aB3xK9mQpR7vLw2n'");
  configurePortal(wm, tokenParam);
  Serial.println("Beállítás portal indul (rövid ideig módosítható)");
  wm.startConfigPortal("campsite");

  // A portalban esetlegesen módosított token átvétele.
  const char *newToken = tokenParam.getValue();
  if (newToken && strlen(newToken) > 0) {
    strncpy(auth_token, newToken, sizeof(auth_token));
    auth_token[sizeof(auth_token) - 1] = '\0';
  }

  // Csak változás esetén mentünk flash-be.
  if (shouldSaveConfig) {
    prefs.putString("token", auth_token);
  }
  prefs.putString("campid", camp_id);
  prefs.end();

  if (WiFi.status() != WL_CONNECTED) {
    WiFi.begin();
    Serial.print("WiFi csatlakozás");
    const unsigned long wifiStart = millis();
    while (WiFi.status() != WL_CONNECTED && (millis() - wifiStart) < 15000) {
      delay(300);
      Serial.print(".");
    }
    Serial.println();
  }

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi kapcsolódás sikertelen");
    ESP.restart();
  }

  Serial.print("IP: ");
  Serial.println(WiFi.localIP());
  Serial.println("Szkennelés indult...");
}

void loop() {
  unsigned long now = millis();

  if (WiFi.status() != WL_CONNECTED) {
    WiFi.reconnect();
    delay(300);
    return;
  }

  // Időközönként új kép küldése a backendnek.
  if (now - lastScanTime >= SCAN_INTERVAL_MS) {
    lastScanTime = now;
    camera_fb_t *fb = esp_camera_fb_get();
    if (fb) {
      sendToLaravel(fb);
      esp_camera_fb_return(fb);
    }
  }
}