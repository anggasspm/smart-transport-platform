# Fleet Service — Integration Checklist

> Gunakan checklist ini untuk memverifikasi Fleet Service siap integrasi dan demo.
> Centang setiap item setelah diverifikasi.

---

## 1. Database

- [ ] Tabel `fleet_routes` ada dan berisi **10 route** (id 1–10)
- [ ] Tabel `fleet_buses` ada dan berisi **30 bus** (id 1–30)
- [ ] Mapping bus-to-route benar: `route_id = INTDIV(bus_id - 1, 3) + 1`
- [ ] Tabel `fleet_gps_logs` bisa menerima INSERT dari `POST /api/gps`
- [ ] Tabel `fleet_incidents` bisa menerima INSERT dari `POST /api/incidents`
- [ ] Foreign key `fleet_buses.route_id → fleet_routes.id` aktif
- [ ] Foreign key `fleet_gps_logs.bus_id → fleet_buses.id` aktif
- [ ] Foreign key `fleet_gps_logs.route_id → fleet_routes.id` aktif
- [ ] Foreign key `fleet_incidents.bus_id → fleet_buses.id` aktif

**Verifikasi cepat:**
```bash
docker exec smarttransport-mysql mysql -uroot -prootpass smarttransport \
  -e "SELECT COUNT(*) AS routes FROM fleet_routes; SELECT COUNT(*) AS buses FROM fleet_buses;"
```

---

## 2. RabbitMQ

- [ ] Exchange `city.events` ada (type: `topic`, durable)
- [ ] Event `gps.update` terkirim saat `POST /api/gps`
- [ ] Event `fleet.incident.created` terkirim saat `POST /api/incidents`
- [ ] Event `bus.status.updated` terkirim saat:
  - Bus status diubah via `PATCH /api/buses/{id}`
  - Incident dibuat (bus → `incident`)
  - Incident di-resolve (bus → `active`)
- [ ] Jika RabbitMQ down, data tetap tersimpan dan response berisi `warnings[]`

**Verifikasi cepat (RabbitMQ Management UI):**
```
http://localhost:15672
Username: admin
Password: adminpass
```
Cek tab **Exchanges** → `city.events` harus ada.

---

## 3. Docker Containers

- [ ] Container `smarttransport-fleet` running dan healthy
- [ ] Container `smarttransport-mysql` running dan healthy
- [ ] Container `smarttransport-rabbitmq` running dan healthy
- [ ] Fleet Service bisa connect ke MySQL (cek `/health`)
- [ ] Fleet Service bisa connect ke RabbitMQ (cek `/health`)

**Verifikasi cepat:**
```bash
docker compose ps | grep -E "fleet|mysql|rabbitmq"
```

---

## 4. API Endpoints

| # | Endpoint | Method | Expected | Status |
|---|---|---|---|---|
| 1 | `/health` | GET | 200, status: healthy | [ ] |
| 2 | `/api/buses` | GET | 200, 30 buses | [ ] |
| 3 | `/api/buses/1` | GET | 200, detail bus #1 | [ ] |
| 4 | `/api/buses/1/location` | GET | 200, GPS terakhir | [ ] |
| 5 | `/api/routes` | GET | 200, 10 routes | [ ] |
| 6 | `/api/routes/1` | GET | 200, detail route #1 | [ ] |
| 7 | `/api/routes/1/buses` | GET | 200, 3 buses | [ ] |
| 8 | `/api/gps` | POST | 201, GPS tersimpan | [ ] |
| 9 | `/api/gps/buses/1` | GET | 200, history GPS | [ ] |
| 10 | `/api/incidents` | POST | 201, incident + bus status=incident | [ ] |
| 11 | `/api/incidents/1/resolve` | PATCH | 200, resolved + bus status=active | [ ] |
| 12 | `/api/buses/999` | GET | 404, not found | [ ] |

---

## 5. Demo Scenario — End-to-End Flow

Jalankan skenario berikut secara berurutan:

### Step 1: Verifikasi data awal
```bash
# 30 bus aktif, 10 route
curl -s http://localhost:8001/api/buses | jq '.data | length'    # → 30
curl -s http://localhost:8001/api/routes | jq '.data | length'   # → 10
```

### Step 2: GPS simulator mengirim data
```bash
curl -s -X POST http://localhost:8001/api/gps \
  -H "Content-Type: application/json" \
  -d '{"bus_id":1,"route_id":1,"lat":-6.3112,"lng":106.8135,"speed_kmh":35,"heading":120,"timestamp":1718340000}' \
  | jq '.code'  # → 201
```

### Step 3: Cek GPS tersimpan di DB
```bash
curl -s http://localhost:8001/api/buses/1/location | jq '.data'
```

### Step 4: Cek RabbitMQ menerima event
Buka RabbitMQ Management → Exchanges → `city.events` → lihat message rate.

### Step 5: Buat incident
```bash
curl -s -X POST http://localhost:8001/api/incidents \
  -H "Content-Type: application/json" \
  -d '{"bus_id":5,"type":"breakdown","severity":"high","description":"Engine overheat"}' \
  | jq '.code'  # → 201
```

### Step 6: Verifikasi bus status berubah
```bash
curl -s http://localhost:8001/api/buses/5 | jq '.data.status'  # → "incident"
```

### Step 7: Resolve incident
```bash
INCIDENT_ID=$(curl -s http://localhost:8001/api/incidents | jq '.data[0].id')
curl -s -X PATCH http://localhost:8001/api/incidents/$INCIDENT_ID/resolve | jq '.code'  # → 200
```

### Step 8: Verifikasi bus kembali active
```bash
curl -s http://localhost:8001/api/buses/5 | jq '.data.status'  # → "active"
```

---

## 6. Risiko Integrasi yang Perlu Diperhatikan

| # | Risiko | Mitigasi |
|---|---|---|
| 1 | RabbitMQ down saat GPS simulator jalan | Data tetap tersimpan, event hilang. Consumer perlu toleransi missing events. |
| 2 | MySQL restart → koneksi pool stale | CodeIgniter reconnect otomatis per-request. |
| 3 | Gateway belum proxy Fleet endpoints | Berikan `fleet-gateway-patch.md` ke tim Gateway. |
| 4 | Concurrent incidents pada bus yang sama | Controller cek incident aktif lain sebelum restore status. |
| 5 | GPS timestamp timezone mismatch | Server menyimpan dalam UTC. Consumer harus handle timezone. |
| 6 | No auth di Fleet Service | Semua auth HARUS di Gateway. Fleet Service trust semua request. |

---

## 7. File Konfigurasi Referensi

| File | Path |
|---|---|
| Dockerfile | `php-fleet/Dockerfile` |
| Entrypoint | `php-fleet/docker-entrypoint.sh` |
| Routes | `php-fleet/app/Config/Routes.php` |
| Env template | `php-fleet/.env.example` |
| DB Schema | `Database/sqema.sql` |
| DB Seed | `Database/seed.sql` |
| docker-compose | `docker-compose.yml` (service: `php-fleet`) |
