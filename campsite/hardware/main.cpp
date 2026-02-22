/*********
  ESP32-S3 QR Scanner - Laravel backend feldolgozással
  Board: Freenove ESP32-S3 WROOM (N16R8)
*********/

#include <Arduino.h>
#include <WiFi.h>
#include <WiFiManager.h>
#include <HTTPClient.h>
#include <Preferences.h>
#include "esp_camera.h"

// ---- Laravel konfig ----
#define LARAVEL_URL  "http://192.168.1.231:8000/api/bookings/scan-image"
// ------------------------

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

// Laravel-nek küldi a JPEG képet feldolgozásra
void sendToLaravel(camera_fb_t *fb) {
  HTTPClient http;
  http.begin(LARAVEL_URL);
  http.addHeader("Content-Type", "image/jpeg");
  http.addHeader("Authorization", "Bearer " + String(auth_token));
  http.addHeader("Accept", "application/json");
  http.addHeader("X-Camp-ID", String(camp_id));

  int httpCode = http.POST(fb->buf, fb->len);

  if (httpCode == 200) {
    String response = http.getString();

    if (response.indexOf("\"valid\":true") >= 0) {
      // Megpróbáljuk kiszedni a message mezőt
      int msgStart = response.indexOf("\"message\":\"");
      if (msgStart >= 0) {
        msgStart += 11;
        int msgEnd = response.indexOf("\"", msgStart);
        String msg = response.substring(msgStart, msgEnd);
        Serial.println("✓ SIKERES: " + msg);
      } else {
        Serial.println("bejelentkezes");
      }
      digitalWrite(48, HIGH);
      delay(1000);
      digitalWrite(48, LOW);
    } else {
      int msgStart = response.indexOf("\"message\":\"");
      if (msgStart >= 0) {
        msgStart += 11;
        int msgEnd = response.indexOf("\"", msgStart);
        String msg = response.substring(msgStart, msgEnd);
        Serial.println("✗ ERVENYTELEN: " + msg);
      } else {
        Serial.println("QR kod");
      }
    }
  } else if (httpCode == 422) {
    // Nincs QR kód a képen
    Serial.println("Nem talalt QR kodot a kepen");
  } else if (httpCode == 401) {
    Serial.println("Token hiba");
  } else {
    Serial.print("HTTP hiba: ");
    Serial.println(httpCode);
  }

  http.end();
}

void setup() {
  Serial.begin(115200);

  pinMode(48, OUTPUT);
  digitalWrite(48, LOW);

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
  config.pin_sscb_sda = CAM_PIN_SIOD;
  config.pin_sscb_scl = CAM_PIN_SIOC;
  config.pin_pwdn     = CAM_PIN_PWDN;
  config.pin_reset    = CAM_PIN_RESET;
  config.xclk_freq_hz = 20000000;
  config.pixel_format = PIXFORMAT_JPEG;
  config.frame_size   = FRAMESIZE_VGA;     // 640x480 (VGA)
  config.jpeg_quality = 10;                // kicsit kisebb fájl, még jó minőség
  config.fb_count     = 2;
  config.fb_location  = CAMERA_FB_IN_PSRAM;
  config.grab_mode    = CAMERA_GRAB_LATEST;

  if (esp_camera_init(&config) != ESP_OK) {
    Serial.println("Kamera  hiba");
    return;
  }

  ledcDetachPin(48);
  digitalWrite(48, LOW);

  // Mentett beállítások betöltése flash-ből
  prefs.begin("config", false);
  strncpy(auth_token, prefs.getString("token", "").c_str(), sizeof(auth_token));
  strncpy(camp_id, prefs.getString("campid", "").c_str(), sizeof(camp_id));

  WiFiManager wm;
  wm.resetSettings(); // <- csak akkor uncommenteld ha új WiFi-re kell átállni

  // CampSite dizájn a captive portalon
  const char* wm_menu[] = {"wifi", "exit"};
  wm.setMenu(wm_menu, 2);
  wm.setTitle("CampSite QR Scanner");
  wm.setCustomHeadElement(
    "<meta charset='UTF-8'>"
    "<style>"
    "body{background:#f5f7f0;font-family:'Segoe UI',Arial,sans-serif;}"
    "h1,h3{color:#516E37;}"
    ".wrap{max-width:400px;margin:0 auto;padding:20px;}"
    "button, .btn{background:#DF8534!important;border:none!important;border-radius:8px!important;color:#fff!important;"
    "font-size:16px!important;padding:12px!important;cursor:pointer;transition:background .2s;}"
    "button:hover,.btn:hover{background:#c97229!important;}"
    "input[type='text'],input[type='password'],select{border:2px solid #ddd!important;border-radius:8px!important;"
    "padding:10px!important;font-size:14px!important;width:100%!important;box-sizing:border-box!important;"
    "transition:border .2s;}"
    "input:focus{border-color:#516E37!important;outline:none!important;}"
    "a{color:#DF8534;text-decoration:none;}"
    "a:hover{color:#516E37;}"
    ".msg{background:#fff;border-left:4px solid #DF8534;padding:10px;margin:10px 0;border-radius:0 8px 8px 0;}"
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
    "for(var i=0;i<l.length;i++){"
    "if(l[i].textContent.trim()=='Password')l[i].innerHTML='Jelszó';"    "if(l[i].textContent.trim()=='Show Password')l[i].innerHTML=' Jelszó megjelenítése';"    "}"
    "var all=document.body.getElementsByTagName('*');"
    "for(var i=0;i<all.length;i++){"
    "if(all[i].children.length==0&&all[i].textContent.indexOf('No AP')>=0)all[i].style.display='none';"
    "}"
    "var h=document.querySelectorAll('h3');"
    "for(var i=0;i<h.length;i++){"
    "if(h[i].textContent.trim()=='CampSite')h[i].style.display='none';"
    "}"
    "};"
    "</script>"
  );
  wm.setCustomMenuHTML("");

  // Egyedi mezők a captive portalon
  WiFiManagerParameter param_divider("<h3 style='margin:15px 0 10px;color:#516E37'>&#128274; Kapu beállítások</h3>");
  WiFiManagerParameter param_campid("campid", "Camp ID", camp_id, 31, "placeholder='pl. 1'");
  WiFiManagerParameter param_token("token", "Bearer Token", auth_token, 127, "type='password' placeholder='pl. aB3xK9mQpR7vLw2n'");
  WiFiManagerParameter param_tokenbtn("<div style='text-align:left;margin:-8px 0 8px;'><label style='cursor:pointer;color:#000;font-size:inherit;'><input type='checkbox' onclick=\"var t=document.getElementById('token');t.type=t.type==='password'?'text':'password';\" style='margin-right:4px;'>Token megjelen&iacute;t&eacute;se</label></div>");
  wm.addParameter(&param_divider);
  wm.addParameter(&param_campid);
  wm.addParameter(&param_token);
  wm.addParameter(&param_tokenbtn);

  Serial.println("WiFi csatlakozás...");
  if (!wm.autoConnect("CampSite", "jelszo123")) {
    Serial.println("WiFi kapcsolódás sikertelen, újraindítás...");
    delay(3000);
    ESP.restart();
  }

  // Beírt értékek mentése flash-be
  if (strlen(param_campid.getValue()) > 0) {
    strncpy(camp_id, param_campid.getValue(), sizeof(camp_id));
    prefs.putString("campid", camp_id);
  }
  if (strlen(param_token.getValue()) > 0) {
    strncpy(auth_token, param_token.getValue(), sizeof(auth_token));
    prefs.putString("token", auth_token);
  }
  prefs.end();
  Serial.print("IP: ");
  Serial.println(WiFi.localIP());
  Serial.println("Szkennelés indult...");
}

void loop() {
  unsigned long now = millis();
  // 500ms-onként küld képet a Laravelnek
  if (now - lastScanTime >= 1000) {
    lastScanTime = now;
    camera_fb_t *fb = esp_camera_fb_get();
    if (fb) {
      sendToLaravel(fb);
      esp_camera_fb_return(fb);
    }
  }
}
