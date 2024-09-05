#include <esp_now.h>
#include <esp_wifi.h>
#include <WiFi.h>
#include <Adafruit_Sensor.h>
#include "Adafruit_SHT4x.h"

// Set your Board ID (ESP32 Sender #1 = BOARD_ID 1, ESP32 Sender #2 = BOARD_ID 2, etc)
#define BOARD_ID 6

Adafruit_SHT4x sht4 = Adafruit_SHT4x();

//MAC Address of the receiver 
uint8_t broadcastAddress[] = {0xE0, 0x5A, 0x1B, 0x30, 0xFB, 0xE8}; //E0:5A:1B:30:FB:E8


//Structure example to send data
//Must match the receiver structure
typedef struct struct_message {
    int id;
    float temp;
    float hum;
    int readingId;
} struct_message;

//Create a struct_message called myData
struct_message myData;

esp_now_peer_info_t peerInfo;

unsigned long previousMillis = 0;   // Stores last time temperature was published
const long interval = 10000;        // Interval at which to publish sensor readings

unsigned int readingId = 0;

// Insert your SSID
constexpr char WIFI_SSID[] = "tartan";

int32_t getWiFiChannel(const char *ssid) {
  if (int32_t n = WiFi.scanNetworks()) {
      for (uint8_t i=0; i<n; i++) {
          if (!strcmp(ssid, WiFi.SSID(i).c_str())) {
              return WiFi.channel(i);
          }
      }
  }
  return 0;
}

sensors_event_t temp, humidity;

float readSHTTemperature() {
  // SHT4x sensöründen sıcaklık okuma
  float t = sht4.getEvent(&humidity, &temp);

  float t_temp = temp.temperature;

  if (isnan(t)) {
    Serial.println("SHT4x sensöründen sıcaklık okunamadı!");
    return 0;
  } else {
    Serial.println(t_temp);
    return t_temp;
  }
}

float readSHTHumidity() {
  // SHT4x sensöründen nem okuma
  float t = sht4.getEvent(&humidity, &temp);

  float t_nem = humidity.relative_humidity;

  if (isnan(t)) {
    Serial.println("SHT4x sensöründen sıcaklık okunamadı!");
    return 0;
  } else {
    Serial.println(t_nem);
    return t_nem;
  }
}

// callback when data is sent
void OnDataSent(const uint8_t *mac_addr, esp_now_send_status_t status) {
  Serial.print("\r\nLast Packet Send Status:\t");
  Serial.println(status == ESP_NOW_SEND_SUCCESS ? "Delivery Success" : "Delivery Fail");
}
 
void setup() {
  //Init Serial Monitor
  Serial.begin(115200);

  Serial.println("Adafruit SHT4x test");
  if (! sht4.begin()) 
  {
    Serial.println("Couldn't find SHT4x");
    while (1) delay(1);
  }
  Serial.println("Found SHT4x sensor");
  Serial.print("Serial number 0x");
  Serial.println(sht4.readSerial(), HEX);

  // You can have 3 different precisions, higher precision takes longer
  sht4.setPrecision(SHT4X_HIGH_PRECISION);

  // You can have 6 different heater settings
  sht4.setHeater(SHT4X_NO_HEATER);
 
  // Set device as a Wi-Fi Station and set channel
  WiFi.mode(WIFI_STA);

  int32_t channel = getWiFiChannel(WIFI_SSID);

  WiFi.printDiag(Serial); // Uncomment to verify channel number before
  esp_wifi_set_promiscuous(true);
  esp_wifi_set_channel(channel, WIFI_SECOND_CHAN_NONE);
  esp_wifi_set_promiscuous(false);
  WiFi.printDiag(Serial); // Uncomment to verify channel change after

  // Init ESP-NOW
  if (esp_now_init() != ESP_OK) {
    Serial.println("Error initializing ESP-NOW");
    return;
  }

  // Once ESPNow is successfully Init, we will register for Send CB to
  // get the status of Trasnmitted packet
  esp_now_register_send_cb(OnDataSent);
  
  // Register peer
  memcpy(peerInfo.peer_addr, broadcastAddress, 6);
  peerInfo.channel = 0;  
  peerInfo.encrypt = false;
  
  // Add peer        
  if (esp_now_add_peer(&peerInfo) != ESP_OK){
    Serial.println("Failed to add peer");
    return;
  }
}
 
void loop() {
  unsigned long currentMillis = millis();
  if (currentMillis - previousMillis >= interval) {
    // Save the last time a new reading was published
    previousMillis = currentMillis;
    //Set values to send
    myData.id = BOARD_ID;
    myData.temp = readSHTTemperature();
    myData.hum = readSHTHumidity();
    myData.readingId = readingId++;
     
    //Send message via ESP-NOW
    esp_err_t result = esp_now_send(broadcastAddress, (uint8_t *) &myData, sizeof(myData));
    if (result == ESP_OK) {
      Serial.println("Sent with success");
    }
    else {
      Serial.println("Error sending the data");
    }
  }
}