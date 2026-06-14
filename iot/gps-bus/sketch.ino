#include <WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>

const char* ssid = "Wokwi-GUEST";
const char* password = "";

const char* mqtt_server = "broker.hivemq.com";

WiFiClient espClient;
PubSubClient client(espClient);

const int BUS_ID = 1;

// posisi bis
int currentRuteKe = 0;

// titik latitude tiap rute
float routeLat[] = {
    -6.3112,
    -6.3115,
    -6.3120,
    -6.3125,
    -6.3130
};

// titik longtitude
float routeLng[] = {
    106.8135,
    106.8140,
    106.8147,
    106.8152,
    106.8160
};

void setupWifi() {
    WiFi.begin(ssid,password);

    while(WiFi.status()!=WL_CONNECTED){
        delay(500);
    }
}

void reconnect() {

    char clientId[30];

    sprintf(
        clientId,
        "BUS_%d",
        BUS_ID
    );

    while (!client.connected()) {

        if(client.connect(clientId)) {

        Serial.println("MQTT sudah konek");

        } else {

        delay(1000);

        }

    }
}

void setup() {
    Serial.begin(115200);

    setupWifi();

    client.setServer(mqtt_server,1883);
}

void loop() {

    if(!client.connected())
        reconnect();

    client.loop();

    StaticJsonDocument<256> doc;

    doc["bus_id"] = BUS_ID;
    doc["lat"] = routeLat[currentRuteKe];
    doc["lng"] = routeLng[currentRuteKe];
    doc["speed_kmh"] = random(20,50);
    doc["heading"] = random(0,360);
    doc["route_id"] = 1;
    doc["timestamp"] = millis();

    char payload[256];

    serializeJson(doc, payload);

    char topic[50];

    sprintf(
        topic,
        "city/bus/%d/gps",
        BUS_ID
    );

    client.publish(
        topic,
        payload
    );

    Serial.println(payload);

    currentRuteKe++;

    if(currentRuteKe>=5)
        currentRuteKe=0;

    delay(30000);
}