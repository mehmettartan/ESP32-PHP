#include <Arduino.h>
#include <WiFi.h>
#include "ESPAsyncWebServer.h"
#include <Arduino_JSON.h>
#include <esp_now.h>

const char* ssid = "tartan";
const char* password = "tartan598";
const char* host = "192.168.160.157";

struct SensorData {
  int sensor_id;
  float temp;
  float hum;
  unsigned int readingId;
};

const int maxSensors = 6; // Maksimum sensör sayısı
SensorData sensorData[maxSensors];
int sensorCount = 1;
unsigned int currentReadingId = 0;

void OnDataRecv(const uint8_t * mac_addr, const uint8_t *incomingData, int len) {
  SensorData data;
  memcpy(&data, incomingData, sizeof(data));

  // Veriyi listeye ekle
  if (sensorCount <= maxSensors) {
    sensorData[sensorCount++] = data;
    // En son okunan readingID'yi sakla
    currentReadingId = data.readingId;
    Serial.printf("Received sensor_id: %d, Temp: %.2f, Humi: %.2f, readingID: %u\n", 
                  data.sensor_id, data.temp, data.hum, data.readingId);
  }
}

void setup() {
  Serial.begin(115200);

  WiFi.mode(WIFI_AP_STA);
  WiFi.begin(ssid, password);

  if (esp_now_init() != ESP_OK) {
    Serial.println("Error initializing ESP-NOW");
    return;
  }

  esp_now_register_recv_cb(OnDataRecv);
}

void loop() {
  static unsigned long lastSendTime = 0;
  unsigned long currentMillis = millis();
  const unsigned long interval = 5000; // 5 saniye

  if (currentMillis - lastSendTime >= interval) {
    lastSendTime = currentMillis;

    if (sensorCount > 0) {
      WiFiClient client;
      const int httpPort = 80;

      // HTTP isteklerini currentReadingId'ye göre gönder
      for (unsigned int i = 0; i < sensorCount; i++) {
        if (sensorData[i].readingId == currentReadingId) {
          if (!client.connect(host, httpPort)) {
            Serial.println("Connection failed");
            return;
          }

          // URL'yi oluştur
          String url = String("/esptowebanddb/index.php?") +
                       "sensor_id=" + String(sensorData[i].sensor_id) +
                       "&Temp=" + String(sensorData[i].temp, 2) +
                       "&Humi=" + String(sensorData[i].hum, 2);

          Serial.println("Requesting URL: " + url);

          // HTTP GET isteğini gönder
          client.print(String("GET ") + url + " HTTP/1.1\r\n" +
                       "Host: " + host + "\r\n" +
                       "Connection: close\r\n\r\n");

          unsigned long timeout = millis();
          while (client.available() == 0) {
            if (millis() - timeout > 1000) {
              Serial.println(">>> Client Timeout !");
              client.stop();
              return;
            }
          }

          client.stop();
        }
      }

      // Verileri sıfırla
      sensorCount = 0;
    }
  }
}