// halte-passenger
#include <WiFi.h>
#include <ArduinoJson.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Adafruit_GFX.h>
#include <Adafruit_ILI9341.h>
#include <PubSubClient.h>

struct StopOption {
  int stop_id;
  String name;
};

#include "config.h"

// config
#define STOP_ID 1
#define RFID_SS_MASUK 5
#define RFID_RST_MASUK 22
#define RFID_SS_KELUAR 15
#define RFID_RST_KELUAR 4

#define TFT_CS 27
#define TFT_DC 26
#define TFT_RST 25

#define BTN_UP 32
#define BTN_DOWN 33
#define BTN_SELECT 14

#define LDR_PIN 34

const char* WIFI_SSID = "Wokwi-GUEST";
const char* WIFI_PASS = "";
const char* MQTT_SERVER = MQTT_SERVER_HOST;
const int MQTT_PORT = MQTT_SERVER_PORT;
const char* MQTT_USER = MQTT_USER_STR;
const char* MQTT_PASS = MQTT_PASS_STR;
const char* API_BASE = API_BASE_URL;

WiFiClient wifiClient;
PubSubClient mqttClient(wifiClient);

// data rute id tiap halte
int getRouteIdForStop(int stop_id) { return (stop_id - 1) / 5 + 1; }

// objek hardware
MFRC522 rfidMasuk(RFID_SS_MASUK, RFID_RST_MASUK);
MFRC522 rfidKeluar(RFID_SS_KELUAR, RFID_RST_KELUAR);
Adafruit_ILI9341 tft(TFT_CS, TFT_DC, TFT_RST);

// state halte
int currentLoad = 0;

int totalMasuk = 0;
int totalKeluar = 0;

unsigned long lastPassengerLog = 0;
#define PASSENGER_LOG_INTERVAL 30000

// state mesin ui untuk scanner masuk
enum UIState { IDLE, VALIDATE_CARD, SHOW_STOPS, WAIT_SELECT, CONFIRM_TICKET };
UIState uiState = IDLE;

String  pendingCardNumber = "";
int selectedDestIdx = 0;

StopOption stopOptions[5];
int stopCount = 0;

struct Holiday {
  uint16_t year;
  uint8_t month;
  uint8_t day;
};

const Holiday HOLIDAYS[] = {
  {2026, 1, 1},   // tahun Baru
  {2026, 1, 27},  // isra miraj
  {2026, 1, 29},  // imlek
  {2026, 3, 29},  // nyepi
  {2026, 3, 31},  // idul fitri
  {2026, 4, 1},   // idul fitri
  {2026, 4, 18},  // wafat isa almasih
  {2026, 4, 20},  // paskah
  {2026, 5, 1},   // hari buruh
  {2026, 5, 12},  // waisak
  {2026, 5, 29},  // kenaikan isa almasih
  {2026, 6, 1},   // hari lahir pancasila
  {2026, 6, 7},   // idul adha
  {2026, 6, 27},  // tahun baru islam
  {2026, 8, 17},  // kemerdekaan indonesia
  {2026, 9, 5},   // maulid nabi
  {2026, 12, 25}  // natal
};

const int NUM_HOLIDAYS = sizeof(HOLIDAYS) / sizeof(Holiday);

// polling bus info
unsigned long lastBusPoll = 0;
#define BUS_POLL_INTERVAL 5000

char replyTopic[40];
char busArrivalTopic[40];

String pendingReqId = "";

volatile bool mqttResponseReceived = false;
String mqttResponsePayload = "";

void selectTFT() {
  digitalWrite(RFID_SS_MASUK, HIGH);
  digitalWrite(RFID_SS_KELUAR, HIGH);
  digitalWrite(TFT_CS, LOW);
}

void deselectTFT() {
  digitalWrite(TFT_CS, HIGH);
}

// setup
void setup() {
  Serial.begin(115200);
  SPI.begin();

  pinMode(RFID_SS_MASUK, OUTPUT);
  pinMode(RFID_SS_KELUAR, OUTPUT);
  pinMode(TFT_CS, OUTPUT);

  digitalWrite(RFID_SS_MASUK, HIGH);
  digitalWrite(RFID_SS_KELUAR, HIGH);
  digitalWrite(TFT_CS, HIGH);

  digitalWrite(RFID_SS_KELUAR, HIGH);
  digitalWrite(TFT_CS, HIGH);

  digitalWrite(RFID_SS_MASUK, LOW);
  rfidMasuk.PCD_Init();
  digitalWrite(RFID_SS_MASUK, HIGH);

  digitalWrite(RFID_SS_KELUAR, LOW);
  rfidKeluar.PCD_Init();
  digitalWrite(RFID_SS_KELUAR, HIGH);

  pinMode(BTN_UP, INPUT_PULLUP);
  pinMode(BTN_DOWN, INPUT_PULLUP);
  pinMode(BTN_SELECT, INPUT_PULLUP);

  pinMode(LDR_PIN, INPUT);

  selectTFT();

  tft.begin();
  tft.fillScreen(ILI9341_BLACK);
  tft.setTextColor(ILI9341_WHITE);
  tft.setTextSize(2);
  tft.printf("Halte id: %d\n", STOP_ID);

  deselectTFT();

  connectWifi();

  snprintf(replyTopic, sizeof(replyTopic), "city/stop/%d/reply", STOP_ID);
  snprintf(busArrivalTopic, sizeof(busArrivalTopic), "city/stop/%d/bus-arrival", STOP_ID);

  mqttClient.setBufferSize(4096);
  mqttClient.setServer(MQTT_SERVER, MQTT_PORT);
  mqttClient.setCallback(mqttCallback);
  connectMqtt();

  showIdle();
}

void loop() {

  if (millis() - lastPassengerLog >= PASSENGER_LOG_INTERVAL) {
    
    Serial.printf(
      "[STATUS 30s] MASUK=%d KELUAR=%d CURRENT_LOAD=%d\n",
      totalMasuk,
      totalKeluar,
      currentLoad
    );

    publishPassengerCount(currentLoad);

    lastPassengerLog = millis();
  }

  // scanner masuk (cuman bisa pas statenya idle)
  if (uiState == IDLE) {
    digitalWrite(TFT_CS, HIGH);
    digitalWrite(RFID_SS_KELUAR, HIGH);
    digitalWrite(RFID_SS_MASUK, LOW);

    if (rfidMasuk.PICC_IsNewCardPresent() && rfidMasuk.PICC_ReadCardSerial()) {
      String card = readCard(rfidMasuk);

      digitalWrite(RFID_SS_MASUK, HIGH);

      rfidMasuk.PICC_HaltA();
      handleCardMasuk(card);

    } else {

      digitalWrite(RFID_SS_MASUK, HIGH);
    }
  }

  // scanner keluar
  digitalWrite(TFT_CS, HIGH);
  digitalWrite(RFID_SS_MASUK, HIGH);
  digitalWrite(RFID_SS_KELUAR, LOW);

  if (rfidKeluar.PICC_IsNewCardPresent() && rfidKeluar.PICC_ReadCardSerial()) {
    String card = readCard(rfidKeluar);
    rfidKeluar.PICC_HaltA();
    handleCardKeluar(card);
  } else {
    digitalWrite(RFID_SS_KELUAR, HIGH);
  }

  // set button pas pilih halte tujuan
  if (uiState == SHOW_STOPS || uiState == WAIT_SELECT) {
    handleButtons();
  }

  delay(100);
}

int getDayOfWeek() {
  struct tm t;
  if (!getLocalTime(&t)) return 0;

  return (t.tm_wday == 0) ? 6 : (t.tm_wday - 1);
}

int getIsHoliday() {
  struct tm t;
  if (!getLocalTime(&t)) return 0;

  int year = t.tm_year + 1900;
  int month = t.tm_mon + 1;
  int day = t.tm_mday;

  for (int i = 0; i < NUM_HOLIDAYS; i++) {
    if (HOLIDAYS[i].year == year &&
        HOLIDAYS[i].month == month &&
        HOLIDAYS[i].day == day) {
      return 1;
    }
  }

  return 0;
}

int getWeather() {

  int light = analogRead(LDR_PIN);

  Serial.printf("LDR = %d\n", light);

  if (light > 3000) {
    return 0; // gelap
  }

  if (light > 1500) {
    return 1; // mendung
  }

  return 2; // cerah
}

void publishPassengerCount(int current_load) {
  if (!mqttClient.connected()) connectMqtt();

  char topic[40];
  snprintf(topic, sizeof(topic), "city/stop/%d/passengers", STOP_ID);

  StaticJsonDocument<256> doc;

  struct tm t;
  getLocalTime(&t);

  doc["stop_id"] = STOP_ID;
  doc["hour"] = t.tm_hour;
  doc["day_of_week"] = getDayOfWeek();
  doc["weather"] = getWeather();
  doc["prev_count"] = current_load;
  doc["is_holiday"] = getIsHoliday();

  char buf[256];
  serializeJson(doc, buf);

  bool ok = mqttClient.publish(topic, buf);

  Serial.printf(
    "[MQTT PASSENGER] %s | current_load=%d | masuk=%d | keluar=%d\n",
    ok ? "PUBLISH OK" : "PUBLISH FAIL",
    current_load,
    totalMasuk,
    totalKeluar
  );

  Serial.printf("[RabbitMQ] %s\n", buf);
}

void connectMqtt() {
  char clientId[32];
  snprintf(clientId, sizeof(clientId), "halte-%d", STOP_ID);
  while (!mqttClient.connected()) {
    if (mqttClient.connect(clientId, MQTT_USER, MQTT_PASS)) {
      Serial.printf("[MQTT] halte %d konek\n", STOP_ID);
      mqttClient.subscribe(replyTopic); // subscribe topic reply buat nerima balasan request
      mqttClient.subscribe(busArrivalTopic);
    } else {
      delay(2000);
    }
  }
}

// ketika kartu di scan
void handleCardMasuk(String cardNumber) {
  Serial.printf("[MASUK] Kartu: %s\n", cardNumber.c_str());
  
  selectTFT();
  
  tft.fillScreen(ILI9341_BLACK);
  tft.setCursor(0,0);
  tft.println("Mengecek kartu...");

  deselectTFT();

  // 1. cek apakah card valid di database passengers
  int passengerId = 0;
  float balance   = 0;
  if (!validateCard(cardNumber, passengerId, balance)) {
    
    showMessage("Kartu tidak valid", ILI9341_RED);
    delay(2000);
    showIdle();
    return;
  }

  // 2. cek apakah passenger punya tiket active
  if (hasActiveTicket(passengerId)) {
    showMessage("Kamu perlu keluar\nhalte terlebih\ndahulu", ILI9341_YELLOW);
    delay(3000);
    showIdle();
    return;
  }

  // 3. ambil semua list halte di rute ini (selain halte stop_id ini)
  stopCount = fetchStopsOnRoute(STOP_ID, stopOptions);
  if (stopCount == 0) {
    showMessage("Gagal ambil data\nhalte", ILI9341_RED);
    delay(2000);
    showIdle();
    return;
  }

  // 4. menampilkan pilihan halte tujuan
  pendingCardNumber = cardNumber;
  selectedDestIdx = 0;
  uiState = SHOW_STOPS;
  showStopMenu();
}

// set button pilihan halte
void handleButtons() {
  if (digitalRead(BTN_UP) == LOW) {
    if (selectedDestIdx > 0) selectedDestIdx--;
    showStopMenu();
    delay(200);
  }
  if (digitalRead(BTN_DOWN) == LOW) {
    if (selectedDestIdx < stopCount - 1) selectedDestIdx++;
    showStopMenu();
    delay(200);
  }
  if (digitalRead(BTN_SELECT) == LOW) {
    uiState = CONFIRM_TICKET;
    int destStopId = stopOptions[selectedDestIdx].stop_id;
    processTicketPurchase(pendingCardNumber, STOP_ID, destStopId);
    delay(200);
  }
}

// proses beli tiket
void processTicketPurchase(String cardNumber, int originStop, int destStop) {
  selectTFT();

  tft.fillScreen(ILI9341_BLACK);
  tft.setCursor(0,0);
  tft.println("Memproses tiket...");

  deselectTFT();

  StaticJsonDocument<256> reqDoc;
  reqDoc["card_number"] = cardNumber;
  reqDoc["origin_stop_id"] = originStop;
  reqDoc["dest_stop_id"] = destStop;
  reqDoc["req_id"] = String(millis());
  reqDoc["reply_to"] = replyTopic;
  String body;
  serializeJson(reqDoc, body);

  String resp;
  bool success = mqttRequestResponse("city/tickets/check-and-create", body, resp);

  StaticJsonDocument<256> respDoc;
  if (success) deserializeJson(respDoc, resp);

  if (success && respDoc["ok"] == true) {
    // jika berhasil: current_load++
    currentLoad++;
    totalMasuk++; 
    showMessage("Tiket siap\nWelcome to halte", ILI9341_GREEN);
    delay(2000);
  } else {
    // jika ada error
    String msg = respDoc["message"] | "Gagal beli tiket";
    showMessage(msg, ILI9341_RED);
    delay(2000);
  }

  uiState = IDLE;
  showIdle();
}

// scan kartu keluar
void handleCardKeluar(String cardNumber) {
  Serial.printf("[KELUAR] Kartu: %s\n", cardNumber.c_str());

  selectTFT();

  tft.fillScreen(ILI9341_BLACK);
  tft.setCursor(0,0);
  tft.println("Proses keluar...");

  deselectTFT();

  StaticJsonDocument<128> reqDoc;
  reqDoc["card_number"] = cardNumber;
  reqDoc["exit_stop_id"] = STOP_ID;
  reqDoc["req_id"] = String(millis());
  reqDoc["reply_to"] = replyTopic;
  String body;
  serializeJson(reqDoc, body);

  String resp;
  bool success = mqttRequestResponse("city/tickets/checkout", body, resp);

  Serial.println("========== CHECKOUT ==========");
  Serial.println(success);
  Serial.println(resp);
  Serial.println("==============================");

  StaticJsonDocument<512> respDoc;
  DeserializationError err = deserializeJson(respDoc, resp);

  Serial.print("deserialize = ");
  Serial.println(err.c_str());

  serializeJsonPretty(respDoc, Serial);
  Serial.println();

  if (success) deserializeJson(respDoc, resp);

  if (success && respDoc["ok"] == true) {
    if (currentLoad > 0) currentLoad--;
    totalKeluar++;
    showMessage("Selamat jalan!\nTerima kasih", ILI9341_GREEN);
  } else {
    String msg = respDoc["message"] | "Gagal checkout";
    showMessage(msg, ILI9341_YELLOW);
  }
  delay(2000);
  showIdle();
  if (uiState == IDLE) {
    showIdle();
  }
}

// pool bus info
void pollBusAtStop() {
  char topic[40];
  snprintf(topic, sizeof(topic), "city/stop/%d/bus-status/get", STOP_ID);

  StaticJsonDocument<128> reqDoc;
  reqDoc["req_id"] = String(millis());
  reqDoc["reply_to"] = replyTopic;
  String body;
  serializeJson(reqDoc, body);

  String resp;
  if (!mqttRequestResponse(topic, body, resp)) {
    return;
  }

  StaticJsonDocument<256> doc;
  deserializeJson(doc, resp);

  if (!doc["data"]["bus_id"].isNull()) {
    int busId = doc["data"]["bus_id"].as<String>().toInt();
    int boarded = doc["data"]["boarded"].as<String>().toInt();
    int alighted = doc["data"]["alighted"].as<String>().toInt();

    selectTFT();
    tft.fillRect(0, 100, 320, 30, ILI9341_BLACK);
    tft.setCursor(0, 100);
    tft.setTextColor(ILI9341_CYAN);
    tft.printf("Bus %d di halte\nNaik:%d Turun:%d", busId, boarded, alighted);
    tft.setTextColor(ILI9341_WHITE);
    deselectTFT();
  }
}

// validasi kartu
bool validateCard(String cardNumber, int& passengerId, float& balance) {
  StaticJsonDocument<192> reqDoc;
  reqDoc["card_number"] = cardNumber;
  reqDoc["req_id"] = String(millis());
  reqDoc["reply_to"] = replyTopic;
  String body;
  serializeJson(reqDoc, body);

  String resp;
  if (!mqttRequestResponse("city/passengers/by-card/get", body, resp)) {
    return false;
  }

  StaticJsonDocument<1028> doc;
  deserializeJson(doc, resp);

  if (doc["data"].isNull()) {
    passengerId = 0;
    return false;
  }

  passengerId = doc["data"]["id"].as<String>().toInt();
  balance = doc["data"]["balance"].as<String>().toFloat();

  return passengerId > 0;
}

// apakah orang itu belum keluar halte
bool hasActiveTicket(int passengerId) {
  StaticJsonDocument<128> reqDoc;
  reqDoc["passenger_id"] = passengerId;
  reqDoc["req_id"] = String(millis());
  reqDoc["reply_to"] = replyTopic;
  String body;
  serializeJson(reqDoc, body);

  String resp;
  bool hasActive = false;
  if (mqttRequestResponse("city/tickets/active/get", body, resp)) {
    StaticJsonDocument<128> doc;
    deserializeJson(doc, resp);
    hasActive = doc["data"]["has_active"] | false;
  }
  return hasActive;
}

// ambil semua halte di rute ini, selain halte ini
int fetchStopsOnRoute(int currentStopId, StopOption* options) {
  int routeId = getRouteIdForStop(currentStopId);

  StaticJsonDocument<192> reqDoc;
  reqDoc["route_id"] = routeId;
  reqDoc["except_stop_id"] = currentStopId;
  reqDoc["req_id"] = String(millis());
  reqDoc["reply_to"] = replyTopic;
  String body;
  serializeJson(reqDoc, body);

  String resp;
  int count = 0;
  if (mqttRequestResponse("city/stops/route/get", body, resp)) {

    Serial.println("========== RESPONSE ==========");
    Serial.println(resp);
    Serial.println("==============================");

    DynamicJsonDocument doc(4096);

    DeserializationError err = deserializeJson(doc, resp);

    if (err) {
        Serial.print("deserialize error: ");
        Serial.println(err.c_str());
        Serial.println(resp);
        return 0;
    }

    Serial.print("ERR = ");
    Serial.println(err.c_str());

    JsonArray arr = doc["data"].as<JsonArray>();

    Serial.print("SIZE = ");
    Serial.println(arr.size());

    deserializeJson(doc, resp);
    for (JsonObject s : arr) {
      if (count >= 5) break;
      options[count].stop_id = s["id"].as<String>().toInt();
      options[count].name = s["name"].as<String>();
      count++;
    }
  }
  return count;
}

void mqttCallback(char* topic, byte* payload, unsigned int length) {
  String msg;
  for (unsigned int i = 0; i < length; i++) {
    msg += (char)payload[i];
  }

  if (String(topic) == busArrivalTopic) {
    StaticJsonDocument<128> doc;
    DeserializationError err = deserializeJson(doc, msg);
    if (err) return;

    int busId = doc["bus_id"] | -1;
    int boarded = doc["boarded"] | 0;
    int alighted = doc["alighted"] | 0;

    Serial.printf("[BUS-ARRIVAL] bus=%d naik=%d turun=%d\n", busId, boarded, alighted);

    if (boarded > 0) {
      currentLoad -= boarded;
      currentLoad += alighted;
      if (currentLoad < 0) currentLoad = 0;
    }

    selectTFT();
    tft.fillRect(0, 100, 320, 30, ILI9341_BLACK);
    tft.setCursor(0, 100);
    tft.setTextColor(ILI9341_CYAN);
    tft.printf("Bus %d di halte\nNaik:%d Turun:%d", busId, boarded, alighted);
    tft.setTextColor(ILI9341_WHITE);
    deselectTFT();
    return;
  }

  StaticJsonDocument<256> check;
  DeserializationError err = deserializeJson(check, msg);
  if (!err) {
    String incomingReqId = check["req_id"].as<String>();
    if (incomingReqId != pendingReqId) {
      Serial.printf("[CALLBACK] IGNORED, req_id beda (expect=%s got=%s)\n", pendingReqId.c_str(), incomingReqId.c_str());
      return;
    }
  }

  Serial.printf("[CALLBACK] topic=%s payload=%s\n", topic, msg.c_str());
  mqttResponsePayload = msg;
  mqttResponseReceived = true;
}

bool mqttRequestResponse(String topic, String payload, String &response) {
  StaticJsonDocument<256> reqCheck;
  deserializeJson(reqCheck, payload);
  pendingReqId = reqCheck["req_id"].as<String>();

  mqttResponseReceived = false;
  mqttResponsePayload = "";
  mqttClient.publish(topic.c_str(), payload.c_str());

  unsigned long start = millis();
  while (!mqttResponseReceived && millis() - start < 30000) {
    mqttClient.loop();
    delay(10);
  }

  if (mqttResponseReceived) {
    response = mqttResponsePayload;
    return true;
  }

  return false;
}

// ui
String readCard(MFRC522& reader) {
  String uid = "";

  for (byte i = 0; i < reader.uid.size; i++) {
    char buf[3];
    sprintf(buf, "%02X", reader.uid.uidByte[i]);
    uid += buf;
  }

  return uid;
}

void showIdle() {
  selectTFT();

  tft.fillScreen(ILI9341_BLACK);
  tft.setCursor(0,0);
  tft.setTextColor(ILI9341_WHITE);
  tft.printf("Halte id: %d\n", STOP_ID);
  tft.printf("Orang: %d\n", currentLoad);
  tft.println("\nTap kartu untuk");
  tft.println("masuk halte");

  deselectTFT();

  uiState = IDLE;
}

void showMessage(String msg, uint16_t color) {
  selectTFT();

  tft.fillScreen(ILI9341_BLACK);
  tft.setCursor(0, 40);
  tft.setTextColor(color);
  tft.println(msg);
  tft.setTextColor(ILI9341_WHITE);

  deselectTFT();
}

void showStopMenu() {
  selectTFT();
  
  tft.fillScreen(ILI9341_BLACK);
  tft.setCursor(0,0);
  tft.setTextColor(ILI9341_YELLOW);
  tft.println("Pilih Tujuan:");
  tft.setTextColor(ILI9341_WHITE);

  int visibleStart = max(0, selectedDestIdx - 2);
  int visibleEnd = min(stopCount - 1, visibleStart + 4);

  for (int i = visibleStart; i <= visibleEnd; i++) {
    if (i == selectedDestIdx) {
      tft.setTextColor(ILI9341_GREEN);
      tft.print("> ");
    } else {
      tft.setTextColor(ILI9341_WHITE);
      tft.print("  ");
    }
    tft.println(stopOptions[i].name);
  }
  tft.setTextColor(ILI9341_WHITE);

  deselectTFT();
}

void connectWifi() {
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  Serial.print("Konek ke wifi");
  while (WiFi.status() != WL_CONNECTED) { delay(500); Serial.print("."); }
  Serial.println(" OK");
}