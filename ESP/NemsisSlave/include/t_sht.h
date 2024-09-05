#pragma once

#include "Adafruit_SHT4x.h"

Adafruit_SHT4x sht4 = Adafruit_SHT4x();

#define BOARD_ID 2

  typedef struct struct_message {
  int id;
  volatile float temp;
  volatile float hum;
  int readingId;
  } struct_message;


void shtsetup() 
{
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
  
}