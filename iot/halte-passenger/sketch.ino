#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Adafruit_GFX.h>
#include <Adafruit_ILI9341.h>
#include <PubSubClient.h>

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

const char* WIFI_SSID = "Wokwi-GUEST";
const char* WIFI_PASS = "";
const char* MQTT_SERVER = "192.168.1.100";
const int MQTT_PORT = 1883;
const char* MQTT_USER = "iot";
const char* MQTT_PASS = "iotpassword";
const char* API_BASE = "http://192.168.1.100:3000";

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

// state mesin ui untuk scanner masuk
enum UIState { IDLE, VALIDATE_CARD, SHOW_STOPS, WAIT_SELECT, CONFIRM_TICKET };
UIState uiState = IDLE;

String  pendingCardNumber = "";
int selectedDestIdx = 0;

struct StopOption {
  int stop_id;
  String name;
};

StopOption stopOptions[5];
int stopCount = 0;

// polling bus info
unsigned long lastBusPoll = 0;
#define BUS_POLL_INTERVAL 5000

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

  selectTFT();

  tft.begin();
  tft.setRotation(1);
  tft.fillScreen(ILI9341_BLACK);
  tft.setTextColor(ILI9341_WHITE);
  tft.setTextSize(2);
  tft.println("Halte IoT");
  tft.printf("Halte ID: %d\n", STOP_ID);

  deselectTFT();

  connectWifi();

  mqttClient.setServer(MQTT_SERVER, MQTT_PORT);
  connectMqtt();

  showIdle();
}

void loop() {
  // poll info bus di halte ini
  if (millis() - lastBusPoll >= BUS_POLL_INTERVAL) {
    pollBusAtStop();
    lastBusPoll = millis();
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

void publishPassengerCount(int boarded, int alighted, int current_load) {
  if (!mqttClient.connected()) connectMqtt();

  char topic[40];
  snprintf(topic, sizeof(topic), "city/stop/%d/passengers", STOP_ID);

  StaticJsonDocument<200> doc;
  doc["stop_id"] = STOP_ID;
  doc["bus_id"] = JsonVariant(); // null
  doc["boarded"] = boarded;
  doc["alighted"] = alighted;
  doc["current_load"] = current_load;
  doc["timestamp"] = millis();

  char buf[200];
  serializeJson(doc, buf);
  mqttClient.publish(topic, buf);
  Serial.printf("[MQTT] halte publish: %s\n", buf);
}

void connectMqtt() {
  char clientId[32];
  snprintf(clientId, sizeof(clientId), "halte-%d", STOP_ID);
  while (!mqttClient.connected()) {
    if (mqttClient.connect(clientId, MQTT_USER, MQTT_PASS)) {
      Serial.printf("[MQTT] halte %d konek\n", STOP_ID);
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

  // POST ke passenger service buat check saldo user dan buat tiket
  HTTPClient http;
  String url = String(API_BASE) + "/api/tickets/check-and-create";
  http.begin(url);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<256> doc;
  doc["card_number"] = cardNumber;
  doc["origin_stop_id"] = originStop;
  doc["dest_stop_id"] = destStop;
  String body;
  serializeJson(doc, body);

  int code = http.POST(body);
  String resp = http.getString();
  http.end();

  if (code == 200 || code == 201) {
    // jika berhasil: current_load++
    currentLoad++;
    publishPassengerCount(1, 0, currentLoad) 
    showMessage("Tiket siap\nWelcome to halte", ILI9341_GREEN);
    delay(2000);
  } else {
    // jika ada error
    StaticJsonDocument<256> errDoc;
    deserializeJson(errDoc, resp);
    String msg = errDoc["message"] | "Gagal beli tiket";
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

  // POST ke passenger service untuk ubah status tiket jadi 'used'
  HTTPClient http;
  String url = String(API_BASE) + "/api/tickets/checkout";
  http.begin(url);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<128> doc;
  doc["card_number"] = cardNumber;
  doc["exit_stop_id"] = STOP_ID;
  String body;
  serializeJson(doc, body);

  int code = http.POST(body);
  String resp = http.getString();
  http.end();

  if (code == 200) {
    if (currentLoad > 0) currentLoad--;
    publishPassengerCount(0, 1, currentLoad) 
    showMessage("Selamat jalan!\nTerima kasih", ILI9341_GREEN);
  } else {
    StaticJsonDocument<256> errDoc;
    deserializeJson(errDoc, resp);
    String msg = errDoc["message"] | "Gagal checkout";
    showMessage(msg, ILI9341_YELLOW);
  }
  delay(2000);
  showIdle();
}

// pool bus info
void pollBusAtStop() {
  HTTPClient http;
  String url = String(API_BASE) + "/api/stops/" + String(STOP_ID) + "/bus-status";
  http.begin(url);
  int code = http.GET();
  if (code == 200) {
    String resp = http.getString();
    StaticJsonDocument<256> doc;
    deserializeJson(doc, resp);

    if (!doc["data"]["bus_id"].isNull()) {
      int busId    = doc["data"]["bus_id"];
      int boarded  = doc["data"]["boarded"]  | 0;
      int alighted = doc["data"]["alighted"] | 0;

      selectTFT();
      
      tft.fillRect(0, 100, 320, 30, ILI9341_BLACK);
      tft.setCursor(0, 100);
      tft.setTextColor(ILI9341_CYAN);
      tft.printf("Bus %d di halte\nNaik:%d Turun:%d", busId, boarded, alighted);
      tft.setTextColor(ILI9341_WHITE);

      deselectTFT();
    }
  }
  http.end();
}

// update total orang di halte
void updateCurrentLoad() {
  HTTPClient http;
  String url = String(API_BASE) + "/api/passengers/count";
  http.begin(url);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<64> doc;
  doc["stop_id"] = STOP_ID;
  doc["current_load"] = currentLoad;
  String body;
  serializeJson(doc, body);

  int code = http.POST(body);
  Serial.printf("[HTTP] POST current_load=%d jadi %d\n", currentLoad, code);
  http.end();
}

// validasi kartu
bool validateCard(String cardNumber, int& passengerId, float& balance) {
  HTTPClient http;
  String url = String(API_BASE) + "/api/passengers/by-card/" + String(cardNumber);
  http.begin(url);
  int code = http.GET();
  if (code == 200) {
    String resp = http.getString();
    StaticJsonDocument<256> doc;
    deserializeJson(doc, resp);
    passengerId = doc["data"]["id"] | 0;
    balance     = doc["data"]["balance"] | 0.0f;
    http.end();
    return passengerId > 0;
  }
  http.end();
  return false;
}

// apakah orang itu belum keluar halte
bool hasActiveTicket(int passengerId) {
  HTTPClient http;
  String url = String(API_BASE) + "/api/tickets/active/" + String(passengerId);
  http.begin(url);
  int code = http.GET();
  bool hasActive = false;
  if (code == 200) {
    String resp = http.getString();
    StaticJsonDocument<128> doc;
    deserializeJson(doc, resp);
    hasActive = doc["data"]["has_active"] | false;
  }
  http.end();
  return hasActive;
}

// ambil semua halte di rute ini, selain halte ini
int fetchStopsOnRoute(int currentStopId, StopOption* options) {
  int routeId = getRouteIdForStop(currentStopId);
  HTTPClient http;
  String url = String(API_BASE) + "/api/stops/route/" + String(routeId) + "/except/" + String(currentStopId);
  http.begin(url);
  int code = http.GET();
  int count = 0;
  if (code == 200) {
    String resp = http.getString();
    DynamicJsonDocument doc(1024);
    deserializeJson(doc, resp);
    JsonArray arr = doc["data"].as<JsonArray>();
    for (JsonObject s : arr) {
      if (count >= 5) break;
      options[count].stop_id = s["id"];
      options[count].name = s["name"].as<String>();
      count++;
    }
  }
  http.end();
  return count;
}

// ui
String readCard(MFRC522& reader) {
  String card = "";
  for (byte i = 0; i < reader.uid.size; i++) {
    if (reader.uid.uidByte[i] < 0x10) card += "0";
    card += String(reader.uid.uidByte[i], HEX);
  }
  card.toUpperCase();
  return card;
}

void showIdle() {
  selectTFT();

  tft.fillScreen(ILI9341_BLACK);
  tft.setCursor(0,0);
  tft.setTextColor(ILI9341_WHITE);
  tft.printf("Halte ID: %d\n", STOP_ID);
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