#include <ESP8266WiFi.h> 
#include <ESP8266WebServer.h>
#include <ArduinoJson.h>
#include "DHT.h"                                        // Подключаем библиотеку DHT

/* Установите здесь свои SSID и пароль */
const char* ssid = "kv189";  // SSID
const char* password = "123467890"; // пароль

#define DHTTYPE DHT11                                   // Используемый датчик DHT 11
uint8_t DHTPin = D2;                                       // Пин к которому подключен датчик
DHT dht(DHTPin, DHTTYPE);                               // Инициализируем датчик

// Объект веб-сервера. Будет прослушивать порт 80 (по умолчанию для HTTP)
ESP8266WebServer server(80);  
 
void handleAllArgs() //обработчик
{
  String message;

  float t=dht.readTemperature();
  while(t<5 && t>50)                  // Запрос на считывание температуры
    t=dht.readTemperature();
  Serial.println(t);
  float h=dht.readHumidity();         // Запрос на считывание влажности
  while(h<1 && h>100)
    h=dht.readHumidity();
  Serial.println(h);

  // Строим документ
  StaticJsonDocument<256> doc;
  // Добавляем строковый элемент с именем "temp" и значением температуры
  doc["temp"] = t;
  // Добавляем числовой элемент с именем "Hum" и значением влажности
  doc["Hum"] = h;
  String output;
  // Серилизуем в строку
  serializeJson(doc, output);
  // Отправляем ответ клиенту
  server.send(200, "application/json", output);  
}

void handleTemp() 
{ 
  server.send(200, "text/plain", (String)dht.readTemperature());    // возвращаем HTTP-ответ
}

void setup() 
{
  Serial.begin(115200);
  delay(100);

  Serial.println("Connecting to ");
  Serial.println(ssid);

  // подключиться к вашей локальной wi-fi сети
  WiFi.begin(ssid, password);

  // проверить, подключился ли wi-fi модуль к wi-fi сети
  while (WiFi.status() != WL_CONNECTED) 
  {
    delay(1000);
    Serial.print(".");
  }

  Serial.println("");
  Serial.println("WiFi connected..!");
  Serial.print("Got IP: ");  
  Serial.println(WiFi.localIP());

  server.on("/all", handleAllArgs);  // привязать функцию обработчика к URL-пути
  server.on("/temp", handleTemp); // привязать функцию обработчика к URL-пути

  server.begin();                                // запуск сервера
  Serial.println("HTTP server started");  
}

void loop() 
{
  server.handleClient();    // обработка входящих запросов
}
