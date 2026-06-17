#include <WiFi.h>
#include <HTTPClient.h>
#include <PubSubClient.h>
#include <ArduinoJson.h>
#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <math.h>

#define BUS_ID 1
#define RFID_SS_PIN 5
#define RFID_RST_PIN 4
#define SCAN_WINDOW_MS 60000

const char* WIFI_SSID = "Wokwi-GUEST";
const char* WIFI_PASS = "";
const char* MQTT_SERVER = "192.168.1.100";
const int MQTT_PORT = 1883;
const char* MQTT_USER = "iot";
const char* MQTT_PASS = "iotpassword";
const char* API_BASE = "http://192.168.1.100:3000";

#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64

unsigned long lastUpdateOLED = 0;

Adafruit_SSD1306 display(
  SCREEN_WIDTH,
  SCREEN_HEIGHT,
  &Wire,
  -1
);

struct Stop {
  int stop_id;
  double lat;
  double lng;
  int seq;
};

// 10 rute, 5 halte per rute
const Stop ROUTES[10][5] = {

  // route 1 (stop_id 1-5)
  {{1,-6.2897154,106.7748231,1},{2,-6.2920817,106.7926304,2},{3,-6.2789535,106.7974812,3},{4,-6.2446481,106.7986129,4},{5,-6.1949978,106.8229143,5}},
  
  // route 2 (stop_id 6-10)
  {{6,-6.1849376,106.8992147,1},{7,-6.1974165,106.8817158,2},{8,-6.2118263,106.8665941,3},{9,-6.1765312,106.8419054,4},{10,-6.1664871,106.8171837,5}},
  
  // route 3 (stop_id 11-15)
  {{11,-6.1375589,106.7039712,1},{12,-6.1479425,106.7203855,2},{13,-6.1583321,106.7516403,3},{14,-6.1615407,106.7904485,4},{15,-6.1627506,106.8342139,5}},
  
  // route 4 (stop_id 16-20)
  {{16,-6.1845113,106.8994725,1},{17,-6.1934867,106.8789301,2},{18,-6.2023187,106.8604405,3},{19,-6.2252641,106.8309488,4},{20,-6.2008427,106.8228772,5}},
  
  // route 5 (stop_id 21-25)
  {{21,-6.1239176,106.8423198,1},{22,-6.1496264,106.8429486,2},{23,-6.1377845,106.8236074,3},{24,-6.1816282,106.8663321,4},{25,-6.2147593,106.8664757,5}},
  
  // route 6 (stop_id 26-30)
  {{26,-6.3023171,106.8202469,1},{27,-6.2853877,106.8422104,2},{28,-6.2436931,106.8427149,3},{29,-6.2216942,106.8248527,4},{30,-6.2008427,106.8228772,5}},
  
  // route 7 (stop_id 31-35)
  {{31,-6.3090174,106.8842732,1},{32,-6.2943115,106.8784338,2},{33,-6.2421847,106.8738917,3},{34,-6.2251786,106.8682375,4},{35,-6.2147593,106.8664757,5}},
  
  // route 8 (stop_id 36-40)
  {{36,-6.2897154,106.7748231,1},{37,-6.2669803,106.7822158,2},{38,-6.2270624,106.7993127,3},{39,-6.1753926,106.8271528,4},{40,-6.1664871,106.8171837,5}},
  
  // route 9 (stop_id 41-45)
  {{41,-6.2901135,106.8819158,1},{42,-6.2990725,106.8891244,2},{43,-6.2421274,106.8668816,3},{44,-6.1718619,106.7928488,4},{45,-6.1178063,106.7906397,5}},
  
  // route 10 (stop_id 46-50)
  {{46,-6.2446481,106.7986129,1},{47,-6.2388634,106.7999816,2},{48,-6.1944815,106.8387476,3},{49,-6.1573727,106.8508913,4},{50,-6.1048235,106.8805447,5}}
};

// buat nentuin rute tiap bus, rute 1 (bus 1-3)
int getRouteId() { return (BUS_ID - 1) / 3 + 1; }
int getRouteIdx() { return getRouteId() - 1; }

// posisi awal bus, jarak 2 hate baru bus selanjutnya jalan
int getInitialStop() {
  int posInRoute = (BUS_ID - 1) % 3; // 0,1,2
  if (posInRoute == 0) return 0;
  if (posInRoute == 1) return 2;
  return 4;
}

// beberapa kemungkinan event bus
enum BusState { MOVING, AT_STOP, TRAFFIC, BREAKDOWN, COOLING };

WiFiClient wifiClient;
PubSubClient mqttClient(wifiClient);
MFRC522 rfid(RFID_SS_PIN, RFID_RST_PIN);

// status bus
BusState busState = MOVING;
int currentStopIdx  = 0;   // posisi bus dekat dengan halte ke berapa
bool goingForward = true;
double currentLat = 0;
double currentLng = 0;
double currentHeading = 0;
float engineTemp = 85.0;
int passengerCount = 0;
int speed_kmh = 30;

String oledMessage = "";
unsigned long oledMessageUntil = 0;

#define MAX_PASSENGERS 40

String passengersInBus[MAX_PASSENGERS];
int passengerCardCount = 0;
String lastCardUID = "";
unsigned long lastCardScan = 0;

// kalau bus stop di halte (AT_STOP)
unsigned long stopArrivalTime = 0;
unsigned long stopDuration = 0;  // berhenti 30-90 detik
int currentStopId = 0;
int boardedAtStop = 0;
int alightedAtStop = 0;
bool scanActive = false;
unsigned long scanStartTime = 0;

// kalau misal bus mogok (BREAKDOWN)
unsigned long breakdownStart  = 0;
bool breakdownActive = false;

// MQTT dan durasi publish
unsigned long lastGpsPublish = 0;
#define GPS_INTERVAL 30000  // kirim data bus terkini tiap 30 detik

int routeId = 1;
int routeIdx = 0;

// setup komponen
void setup() {
  Serial.begin(115200);
  SPI.begin();
  rfid.PCD_Init();

  // inisialisasi oled
  Wire.begin();

  if(!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    while(true);
  }

  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(SSD1306_WHITE);
  display.display();

  routeId = getRouteId();
  routeIdx = getRouteIdx();
  currentStopIdx = getInitialStop();

  // posisi bus & sedang dekat dengan halte mana
  currentLat = ROUTES[routeIdx][currentStopIdx].lat;
  currentLng = ROUTES[routeIdx][currentStopIdx].lng;
  currentStopId = ROUTES[routeIdx][currentStopIdx].stop_id;

  connectWifi();

  configTime(7 * 3600, 0, "pool.ntp.org", "time.nist.gov");

  struct tm timeinfo;
  while (!getLocalTime(&timeinfo)) {
    delay(1000);
  }

  mqttClient.setServer(MQTT_SERVER, MQTT_PORT);
  connectMqtt();

  Serial.printf("BUS %d pada rute %d, mulai berjalan dari stop_id=%d\n", BUS_ID, routeId, currentStopId);
}

// nyari kartu passenger
int findPassengerCard(String cardNumber) {

  for (int i = 0; i < passengerCardCount; i++) {

    if (passengersInBus[i] == cardNumber) {
      return i;
    }

  }

  return -1;
}

// scan naik
bool boardPassenger(String cardNumber) {

  if (passengerCardCount >= MAX_PASSENGERS) {
    return false;
  }

  passengersInBus[passengerCardCount] = cardNumber;
  passengerCardCount++;

  return true;
}

// scan turun
bool alightPassenger(String cardNumber) {

  int idx = findPassengerCard(cardNumber);

  if (idx < 0) {
    return false;
  }

  for (int i = idx; i < passengerCardCount - 1; i++) {
    passengersInBus[i] = passengersInBus[i + 1];
  }

  passengerCardCount--;

  return true;
}

const char* stateToString() {

  switch (busState) {
    case MOVING: return "Moving";
    case AT_STOP: return "Stop";
    case TRAFFIC: return "Macet";
    case BREAKDOWN: return "Mogok";
    case COOLING: return "Cooling";
  }

  return "-";
}

// untuk pesan bus penuh di oled (5 detik)
void showMessage(String msg, int durationMs = 5000) {

  oledMessage = msg;
  oledMessageUntil = millis() + durationMs;
}

// untuk nampilin teks di oled
void updateOLED() {

  display.clearDisplay();

  if (millis() < oledMessageUntil) {

    display.setCursor(0, 0);

    display.println("---- INFO ----");
    display.println();
    display.println(oledMessage);

    display.display();
    return;
  }

  display.setCursor(0, 0);

  display.print("Bus: ");
  display.println(BUS_ID);

  display.print("Rute: ");
  display.println(routeId);

  display.print("Stop: ");
  display.println(currentStopId);

  display.print("Kpsts: ");
  display.println(passengerCount);

  display.print("Spd: ");
  display.print(speed_kmh);
  display.println(" kmh");

  display.print("Suhu: ");
  display.print(engineTemp);
  display.println(" C");

  display.print("Status : ");
  display.println(stateToString());

  display.display();
}

// loop
void loop() {
  if (!mqttClient.connected()) connectMqtt();
  mqttClient.loop();

  updateBusState();

  if (millis() - lastGpsPublish >= GPS_INTERVAL) {
    publishGps();
    lastGpsPublish = millis();
  }

  // RFID/scanner bus hanya bisa bekerja ketika bus sudah berada di suatu halte
  if (busState == AT_STOP && scanActive) {
    checkRFID();
    if (millis() - scanStartTime > SCAN_WINDOW_MS) {
      scanActive = false;
      Serial.println("[RFID] Scanner ditutup");
    }
  }

  // update data oled tiap 1 detik
  if (millis() - lastUpdateOLED >= 1000) {
    updateOLED();
    lastUpdateOLED = millis();
  }

  delay(100);
}

// update state bus
void updateBusState() {
  switch (busState) {
    case MOVING:
      updateMoving();
      break;
    case AT_STOP:
      updateAtStop();
      break;
    case TRAFFIC:
      updateTraffic();
      break;
    case BREAKDOWN:
      updateBreakdown();
      break;
    case COOLING:
      updateCooling();
      break;
  }
  updateEngineTemp();
  checkOverheat();
}

// update pergerakan bus (bergerak ke arah halte berikutnya)
void updateMoving() {
  int targetIdx;

  if (goingForward)
    targetIdx = min(currentStopIdx + 1, 4);
  else
    targetIdx = max(currentStopIdx - 1, 0);

  // catat heading dan posisi halte berikutnya
  double targetLat = ROUTES[routeIdx][targetIdx].lat;
  double targetLng = ROUTES[routeIdx][targetIdx].lng;

  speed_kmh = getSpeedByHour();

  // jarak gerak bus per 30 detik
  double stepDeg = (speed_kmh / 3600.0) * 30 * (1.0 / 111.0); // 1 derajat ≈ 111 km

  double dLat = targetLat - currentLat;
  double dLng = targetLng - currentLng;
  double dist = sqrt(dLat*dLat + dLng*dLng);

  // update heading
  currentHeading = atan2(dLng, dLat) * 180.0 / PI;
  if (currentHeading < 0) currentHeading += 360;

  if (dist <= stepDeg || dist < 0.0001) {
    // jika sudah dekat dengan posisi halte
    currentLat = targetLat;
    currentLng = targetLng;
    currentStopIdx = targetIdx;
    currentStopId = ROUTES[routeIdx][targetIdx].stop_id;
    arriveAtStop();
  } else {
    // gerak menuju halte
    currentLat += (dLat / dist) * stepDeg;
    currentLng += (dLng / dist) * stepDeg;

    // 5% peluang macet (TRAFFIC) di daerah sekitar halte
    if (random(100) < 5) {
      busState = TRAFFIC;
      Serial.println("[BUS] ada kemacetan di daerah halte [TRAFFIC]");
    }
  }
}

void arriveAtStop() {
  // cek dulu apakah di halte depan masih ada bus lain yang masih nurunin penumpang
  // GET stop_passenger_counts untuk halte ini
  if (isStopOccupied(currentStopId)) {
    Serial.printf("[BUS] Halte %d masih ada bus lain, nunggu bus lain selesai ngangkut penumpang...\n", currentStopId);
    
    // berhenti sebentar sebelum halte sampai bus lain selesai angkut penumpang
    return;
  }

  busState = AT_STOP;
  stopArrivalTime = millis();
  stopDuration = random(30, 91) * 1000UL; // tunggu 30-90 detik
  boardedAtStop = 0;
  alightedAtStop = 0;
  scanActive = true;
  scanStartTime = millis();
  speed_kmh = 0;

  Serial.printf("[BUS %d] Sampai di halte ke %d\n", BUS_ID, currentStopId);

  // update stop_passenger_counts ubah bus_id jadi BUS_ID ini, dan update penumpang yang naik dan turun di halte itu
  updateStopPassengerCount(currentStopId, BUS_ID, boardedAtStop, alightedAtStop);
}

void updateAtStop() {
  if (millis() - stopArrivalTime >= stopDuration) {
    // bus berangkat dari halte
    Serial.printf("[BUS %d] Berangkat dari halte %d, naik=%d, turun=%d\n", BUS_ID, currentStopId, boardedAtStop, alightedAtStop);

    // update stop_passenger_counts ubdah bus_id sekarang jadi NULL (tidak ada bus di halte itu)
    clearStopBusId(currentStopId);

    // set halte berikutnya
    advanceToNextStop();
    busState = MOVING;
    scanActive = false;
  }
}

void advanceToNextStop() {
  // maju (halte 1 ke 5)
  if (goingForward) {
    if (currentStopIdx < 4) {
      currentStopIdx++;
    } else {
      // balik lagi ke halte sebelumnya kalau udah di halte akhir (halte 5)
      goingForward = false;
      currentStopIdx--;
    }
  } else { // !goingForward
    // mundur (halte 5 ke 1)
    if (currentStopIdx > 0) {
      currentStopIdx--;
    } else {
      // maju lagi ketika sudah di halte 1  
      goingForward = true;
      currentStopIdx++;
    }
  }
}

// macet
void updateTraffic() {
  speed_kmh = random(5, 16);

  int targetIdx;

  if (goingForward)
    targetIdx = min(currentStopIdx + 1, 4);
  else
    targetIdx = max(currentStopIdx - 1, 0);

  // bus melambat tapi tetap ke halte tujuan
  double targetLat = ROUTES[routeIdx][currentStopIdx].lat;
  double targetLng = ROUTES[routeIdx][currentStopIdx].lng;
  double stepDeg = (speed_kmh / 3600.0) * 30 * (1.0 / 111.0);
  double dLat = targetLat - currentLat;
  double dLng = targetLng - currentLng;
  double dist = sqrt(dLat*dLat + dLng*dLng);
  if (dist > 0.0001) {
    currentLat += (dLat / dist) * stepDeg;
    currentLng += (dLng / dist) * stepDeg;
  }
  // 20% peluang keluar dari macet
  if (random(100) < 20) {
    busState = MOVING;
    Serial.println("[BUS] keluar dari macet (traffic out)");
  }
}

void updateBreakdown() {
  speed_kmh = 0;
  // minimal bus mogok itu 10 menit buat dinginin mesin
  if (millis() - breakdownStart > 600000UL) {
    busState = COOLING;
    Serial.println("[BUS] mulai mendinginkan mesin setelah mogok");
  }
}

void updateCooling() {
  // suhu turun perlahan-perlahan
  engineTemp -= 0.5;
  if (engineTemp < 110) {
    busState = MOVING;
    Serial.println("[BUS] reparasi mesin selesai, bus kembali jalan setelah sebelumnya mogok");
  }
}

void updateEngineTemp() {
  float target;
  switch (busState) {
    case TRAFFIC: target = 100 + random(20); break;
    case BREAKDOWN: target = 155 + random(5); break;
    case COOLING: /* udah di updateCooling */ return;
    default: target = 82 + random(10); break;
  }
  // suhu berubah pelan sesuai keadaan bus
  if (engineTemp < target) engineTemp += 0.3;
  else if (engineTemp > target) engineTemp -= 0.2;
}

void checkOverheat() {
  if (busState == BREAKDOWN || busState == COOLING) return;
  if (random(1000) < 5) { // kemungkinan overheat 0.5%
    engineTemp = 155 + random(10);
    busState = BREAKDOWN;
    breakdownStart = millis();
    speed_kmh = 0;
    Serial.println("[BUS] mogok karena overheat");
  }
}

bool isStopOccupied(int stop_id) {
  HTTPClient http;
  String url = String(API_BASE) + "/api/stops/" + stop_id + "/bus-status";
  http.begin(url);
  addAuthHeader(http);
  int code = http.GET();
  if (code == 200) {
    String resp = http.getString();
    StaticJsonDocument<256> doc;
    deserializeJson(doc, resp);
    bool occupied = !doc["data"]["bus_id"].isNull();
    http.end();
    return occupied;
  }
  http.end();
  return false;
}

// RFID scanner kartu di bus
void checkRFID() {
  if (!rfid.PICC_IsNewCardPresent() || !rfid.PICC_ReadCardSerial()) return;

  // baca UID sebagai string card_number
  String cardNumber = "";
  for (byte i = 0; i < rfid.uid.size; i++) {
    if (rfid.uid.uidByte[i] < 0x10) cardNumber += "0";
    cardNumber += String(rfid.uid.uidByte[i], HEX);
  }
  cardNumber.toUpperCase();

  if (cardNumber == lastCardUID &&
    millis() - lastCardScan < 3000) { // harus nunggu 3 detik dulu sebelum tap keluar

    rfid.PICC_HaltA();
    return;
  }

  lastCardUID = cardNumber;
  lastCardScan = millis();

  Serial.printf("[RFID BUS] Kartu: %s\n", cardNumber.c_str());

  int idx = findPassengerCard(cardNumber);

  if (idx == -1) {

    // kalau kartu belum ada di bus = naik
    if (boardPassenger(cardNumber)) {

      passengerCount++;
      boardedAtStop++;

      Serial.printf(
        "[BUS] Penumpang dengan card %s naik, total = %d\n",
        cardNumber.c_str(),
        passengerCount
      );

      updateStopPassengerCount(
        currentStopId,
        BUS_ID,
        boardedAtStop,
        alightedAtStop
      );
    } else {

      Serial.println("[BUS] Kapasitas penuh");

      showMessage(
        "Bus penuh\nMaks 40 org",
        5000
      );
    }

  } else {

    // kalau kartu sudah ada di bus = turun
    if (alightPassenger(cardNumber)) {

      if (passengerCount > 0) {
        passengerCount--;
      }

      alightedAtStop++;

      Serial.printf(
        "[BUS] Penumpang %s turun, total = %d\n",
        cardNumber.c_str(),
        passengerCount
      );

      updateStopPassengerCount(
        currentStopId,
        BUS_ID,
        boardedAtStop,
        alightedAtStop
      );
    }
  }

  rfid.PICC_HaltA();
}

// HTTP calls
void addAuthHeader(HTTPClient& http) {
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-IoT-Key", "iot-secret-key"); // Gateway verify header ini
}

void updateStopPassengerCount(int stop_id, int bus_id, int boarded, int alighted) {
  HTTPClient http;
  String url = String(API_BASE) + "/api/stops/" + stop_id + "/bus-arrival";
  http.begin(url);
  addAuthHeader(http);

  StaticJsonDocument<128> doc;
  doc["bus_id"] = bus_id;
  doc["boarded"] = boarded;
  doc["alighted"] = alighted;
  String body;
  serializeJson(doc, body);

  int code = http.PUT(body);
  Serial.printf("[HTTP] PUT bus stop=%d jadi %d\n", stop_id, code);
  http.end();
}

void clearStopBusId(int stop_id) {
  HTTPClient http;
  String url = String(API_BASE) + "/api/stops/" + stop_id + "/bus-departure";
  http.begin(url);
  addAuthHeader(http);
  http.addHeader("Content-Type", "application/json");
  int code = http.PUT("{}");
  Serial.printf("[HTTP] PUT bus stop=%d jadi %d\n", stop_id, code);
  http.end();
}

// MQTT publish gps
void publishGps() {
  char topic[32];
  snprintf(topic, sizeof(topic), "city/bus/%d/gps", BUS_ID);

  StaticJsonDocument<256> doc;
  doc["bus_id"] = BUS_ID;
  doc["route_id"] = routeId;
  doc["lat"] = currentLat;
  doc["lng"] = currentLng;
  doc["speed_kmh"] = speed_kmh;
  doc["heading"] = (int)currentHeading;
  doc["passenger_count"] = passengerCount;
  doc["engine_temp"] = engineTemp;

  char buf[256];
  serializeJson(doc, buf);
  mqttClient.publish(topic, buf);
  Serial.printf("[MQTT] publish: %s\n", buf);
}

// kecepatan berdasarkan jam
int getSpeedByHour() {
  int h = hour();
  if (h >= 6 && h < 9) return random(5, 21);
  if (h >= 9 && h < 15) return random(25, 41);
  if (h >= 15 && h < 19) return random(5, 21);
  if (h >= 19 && h < 22) return random(30, 46);
  return random(40, 61); // malam
}

int hour() {
  struct tm timeinfo;

  if (!getLocalTime(&timeinfo)) {
    return 12;
  }

  return timeinfo.tm_hour;
}

// wifi & mqtt
void connectWifi() {
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  Serial.print("Konek ke wifi");
  while (WiFi.status() != WL_CONNECTED) { delay(500); Serial.print("."); }
  Serial.println(" OK");
}

void connectMqtt() {
  char clientId[32];
  snprintf(clientId, sizeof(clientId), "bus-%d", BUS_ID);
  while (!mqttClient.connected()) {
    if (mqttClient.connect(clientId, MQTT_USER, MQTT_PASS)) {
      Serial.printf("[MQTT] konek sebagai %s\n", clientId);
    } else {
      delay(2000);
    }
  }
}