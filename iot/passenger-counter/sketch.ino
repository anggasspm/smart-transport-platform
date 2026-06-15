#include <WiFi.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>

const char* ssid = "Wokwi-GUEST";
const char* password = "";
const char* mqtt_server = "broker.hivemq.com";

const int BUS_ID = 1;
const int KAPASITAS_BUS = 40;

WiFiClient espClient;
PubSubClient client(espClient);

#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64

Adafruit_SSD1306 display(
    SCREEN_WIDTH,
    SCREEN_HEIGHT,
    &Wire,
    -1
);

#define TRIG_A 5
#define ECHO_A 18

#define TRIG_B 17
#define ECHO_B 16

int boarded = 0;
int alighted = 0;
int currentLoad = 0;


unsigned long lastA = 0;
unsigned long lastB = 0;

int stopIds[] = {1, 2, 3, 4, 5};
int currentStopKe = 0;

long readDistance(int trigPin,int echoPin) {

    digitalWrite(trigPin, LOW);
    delayMicroseconds(2);

    digitalWrite(trigPin, HIGH);
    delayMicroseconds(10);

    digitalWrite(trigPin, LOW);

    long duration = pulseIn(echoPin, HIGH);

    long distance = duration * 0.034 / 2;

    return distance;
}


void updateOLED(){

    display.clearDisplay();

    display.setTextSize(1);
    display.setTextColor(SSD1306_WHITE);

    display.setCursor(0,0);
    display.print("Bus Stop ID ");
    display.println(stopIds[currentStopKe]);

    display.setCursor(0,18);
    display.print("Naik: ");
    display.println(boarded);

    display.setCursor(0,32);
    display.print("Turun: ");
    display.println(alighted);

    display.setCursor(0,46);
    display.print("Total Penumpang: ");
    display.print(currentLoad);
    display.print("/");
    display.println(KAPASITAS_BUS);

    display.display();
}

void publishPassengerData() {

    StaticJsonDocument<256> doc;

    doc["bus_id"] = BUS_ID;
    doc["stop_id"] = stopIds[currentStopKe];
    doc["boarded"] = boarded;
    doc["alighted"] = alighted;
    doc["current_load"] = currentLoad;
    doc["capacity"] = KAPASITAS_BUS;

    doc["timestamp"] = millis();

    char payload[256];

    serializeJson(doc, payload);

    char topic[50];

    sprintf(
        topic,
        "city/bus/%d/passengers",
        BUS_ID
    );

    client.publish(
        topic,
        payload
    );

    Serial.print("mqtt publish: ");
    Serial.println(payload);

    currentStopKe++;

    if (currentStopKe >= (int)(sizeof(stopIds) / sizeof(stopIds[0])))
        currentStopKe = 0;
}

void setupWifi() {

    WiFi.begin(ssid, password);

    while (WiFi.status() != WL_CONNECTED) {
        delay(500);
    }
}

void setup(){

    Serial.begin(115200);

    setupWifi();

    client.setServer(mqtt_server, 1883);

    pinMode(TRIG_A, OUTPUT);
    pinMode(ECHO_A, INPUT);

    pinMode(TRIG_B, OUTPUT);
    pinMode(ECHO_B, INPUT);

    Wire.begin(21,22);

    if(!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
        Serial.println("OLED gagal");
        while(true);
    }

    updateOLED();
    }

    void reconnect() {
    
    char clientId[50];

    sprintf(
        clientId,
        "PASSENGER_%d",
        BUS_ID
    );

    while (!client.connected()) {

        if(client.connect(clientId)) {

        Serial.println("MQTT terkonek");

        } else {

        delay(1000);
        }
    }
}


void loop(){

    if(!client.connected()) {
        reconnect();
    }

    client.loop();

    long distA = readDistance(TRIG_A,ECHO_A);

    long distB = readDistance(TRIG_B,ECHO_B);


    if(distA < 50){

        lastA = millis();
        delay(200);
    }

    if(distB < 50){

        lastB = millis();
        delay(200);
    }

    // naik
    if (lastA > 0 && lastB > lastA && (lastB-lastA) < 2000){

        boarded++;

        if (currentLoad < KAPASITAS_BUS)
            currentLoad++;

        Serial.println(
            "Passenger Boarded"
        );

        updateOLED();

        publishPassengerData();

        lastA = 0;
        lastB = 0;
    }

    // turun
    if(lastB > 0 && lastA > lastB && (lastA-lastB) < 2000){

        alighted++;

        if(currentLoad > 0)
            currentLoad--;

        Serial.println(
            "Passenger Alighted"
        );

        updateOLED();

        publishPassengerData();

        lastA = 0;
        lastB = 0;
    }
    delay(100);
}
