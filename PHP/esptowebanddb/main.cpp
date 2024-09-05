#include <Arduino.h>
#include <WiFi.h>
#include "ESPAsyncWebServer.h"
#include <Arduino_JSON.h>
#include <esp_now.h>

const char* ssid = "tartan";
const char* password = "tartan598";
const char* host = "192.168.55.157";

int sensor_id;
float Temp;
float Humi;

// Structure example to receive data
// Must match the sender structure
typedef struct struct_message {
  int id;
  float temp;
  float hum;
  unsigned int readingId;
} struct_message;

struct_message incomingReadings;

const int relayPins[] = { 12, 13, 14, 15, 25 };

JSONVar board;

AsyncWebServer server(80);
AsyncEventSource events("/events");

// callback function that will be executed when data is received
void OnDataRecv(const uint8_t * mac_addr, const uint8_t *incomingData, int len) { 
  // Copies the sender mac address to a string
  char macStr[18];
  Serial.print("Packet received from: ");
  snprintf(macStr, sizeof(macStr), "%02x:%02x:%02x:%02x:%02x:%02x",
           mac_addr[0], mac_addr[1], mac_addr[2], mac_addr[3], mac_addr[4], mac_addr[5]);
  Serial.println(macStr);
  memcpy(&incomingReadings, incomingData, sizeof(incomingReadings));
  
  sensor_id = incomingReadings.id;
  Temp = incomingReadings.temp;
  Humi = incomingReadings.hum;

  Serial.printf("sensor_id: %u: %u bytes\n", incomingReadings.id, len);
  Serial.printf("temperature: %4.2f \n", incomingReadings.temp);
  Serial.printf("humidity: %4.2f \n", incomingReadings.hum);
  Serial.printf("readingID value: %d \n", incomingReadings.readingId);
  Serial.println();
}

void setup() {
  // put your setup code here, to run once:
  Serial.begin(115200);

      // Röle pinlerini çıkış olarak ayarlama
  for (int i = 0; i < 5; i++) {
    pinMode(relayPins[i], OUTPUT);
    // Röleleri varsayılan olarak kapalı konuma getirme
    digitalWrite(relayPins[i], HIGH);
  }

  // Set the device as a Station and Soft Access Point simultaneously
  WiFi.mode(WIFI_AP_STA);
  WiFi.begin(ssid, password);

    // Init ESP-NOW
  if (esp_now_init() != ESP_OK) {
    Serial.println("Error initializing ESP-NOW");
    return;
  }

    // Once ESPNow is successfully Init, we will register for recv CB to
  // get recv packer info
  esp_now_register_recv_cb(OnDataRecv);
}

void loop() {
  // 200 ms gecikme
  delay(200);

  WiFiClient client;
  const int httpPort = 80;

  // Sunucuya bağlanmayı dene
  if (!client.connect(host, httpPort)) {
    Serial.println("Connection failed");
    return;
  }

  // URL'yi oluştur
  String url = String("/esptowebanddb/index.php?") +
               "sensor_id=" + String(sensor_id) +
               "&Temp=" + String(Temp, 2) +
               "&Humi=" + String(Humi, 2);

  // URL'yi seriyal monitöre yazdır
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

  // Sunucudan gelen yanıtı oku ve seriyal monitöre yazdır
  while (client.available()) {
    String line = client.readStringUntil('\r');
    Serial.print(line);
  }

  // 5 saniye bekle
  delay(5000);
}