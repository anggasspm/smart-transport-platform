# Fleet Service — API Testing Guide

> Base URL: `http://localhost:8001`
>
> Semua response menggunakan envelope JSON standar:
> ```json
> { "status": "success|error", "code": 200, "message": "...", "data": ... }
> ```

---

## 1. Health Check

```bash
curl -s http://localhost:8001/health | jq
```

**Expected:** `200` — `status: healthy` jika DB & RabbitMQ up, `status: degraded` jika RabbitMQ down.

---

## 2. Bus Endpoints

### GET /api/buses — List semua bus

```bash
curl -s http://localhost:8001/api/buses | jq
```

### POST /api/buses — Buat bus baru

```bash
curl -s -X POST http://localhost:8001/api/buses \
  -H "Content-Type: application/json" \
  -d '{
    "plate_number": "B 9999 TST",
    "route_id": 1,
    "capacity": 45,
    "status": "active",
    "driver_name": "Test Driver"
  }' | jq
```

**Expected:** `201 Created`

### GET /api/buses/{id} — Detail bus

```bash
curl -s http://localhost:8001/api/buses/1 | jq
```

### PATCH /api/buses/{id} — Update bus

```bash
curl -s -X PATCH http://localhost:8001/api/buses/1 \
  -H "Content-Type: application/json" \
  -d '{
    "driver_name": "Agus Salim Updated",
    "status": "maintenance"
  }' | jq
```

**Expected:** `200` — Jika status berubah, event `bus.status.updated` dikirim ke RabbitMQ.

### DELETE /api/buses/{id} — Hapus bus

```bash
curl -s -X DELETE http://localhost:8001/api/buses/31 | jq
```

> ⚠️ Gunakan ID bus yang baru dibuat agar tidak menghapus seed data.

### GET /api/buses/{id}/location — Last known GPS location

```bash
curl -s http://localhost:8001/api/buses/1/location | jq
```

**Expected:** Data GPS terakhir dari `fleet_gps_logs` atau `null` jika belum ada.

---

## 3. Route Endpoints

### GET /api/routes — List semua route

```bash
curl -s http://localhost:8001/api/routes | jq
```

### POST /api/routes — Buat route baru

```bash
curl -s -X POST http://localhost:8001/api/routes \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Rute Test Koridor 11",
    "origin": "Terminal Test A",
    "destination": "Terminal Test B",
    "total_stops": 8,
    "distance_km": 15.5,
    "est_duration_min": 45
  }' | jq
```

**Expected:** `201 Created`

### GET /api/routes/{id} — Detail route

```bash
curl -s http://localhost:8001/api/routes/1 | jq
```

### PATCH /api/routes/{id} — Update route

```bash
curl -s -X PATCH http://localhost:8001/api/routes/1 \
  -H "Content-Type: application/json" \
  -d '{
    "total_stops": 20,
    "distance_km": 25.00
  }' | jq
```

### DELETE /api/routes/{id} — Hapus route

```bash
curl -s -X DELETE http://localhost:8001/api/routes/11 | jq
```

> ⚠️ Jangan hapus route 1–10, karena bus masih terhubung via foreign key.

### GET /api/routes/{id}/buses — List bus di route tertentu

```bash
curl -s http://localhost:8001/api/routes/1/buses | jq
```

**Expected:** 3 bus per route (sesuai seed).

---

## 4. GPS Endpoints

### POST /api/gps — Kirim GPS data (format simulator)

**Payload minimal (Unix timestamp):**

```bash
curl -s -X POST http://localhost:8001/api/gps \
  -H "Content-Type: application/json" \
  -d '{
    "bus_id": 1,
    "route_id": 1,
    "lat": -6.3112,
    "lng": 106.8135,
    "speed_kmh": 35,
    "heading": 120,
    "timestamp": 1718340000
  }' | jq
```

**Payload lengkap (dengan optional fields):**

```bash
curl -s -X POST http://localhost:8001/api/gps \
  -H "Content-Type: application/json" \
  -d '{
    "bus_id": 1,
    "route_id": 1,
    "lat": -6.3112,
    "lng": 106.8135,
    "speed_kmh": 35,
    "heading": 120,
    "passenger_count": 20,
    "engine_temp": 87.5,
    "timestamp": 1718340000
  }' | jq
```

**Payload dengan ISO timestamp:**

```bash
curl -s -X POST http://localhost:8001/api/gps \
  -H "Content-Type: application/json" \
  -d '{
    "bus_id": 2,
    "route_id": 1,
    "lat": -6.2088,
    "lng": 106.8456,
    "speed_kmh": 42,
    "heading": 90,
    "timestamp": "2026-06-14T21:30:00+07:00"
  }' | jq
```

**Expected:** `201 Created` — Data tersimpan di `fleet_gps_logs` dan event `gps.update` dikirim ke RabbitMQ.

**Catatan:**
- `timestamp` bisa Unix integer atau ISO string. Jika kosong, server menggunakan waktu sekarang.
- `engine_temp` optional/null.
- `passenger_count` optional, default 0.
- `bus_id` dan `route_id` wajib ada di database.

### GET /api/gps/buses/{bus_id} — History GPS per bus

```bash
curl -s "http://localhost:8001/api/gps/buses/1?limit=10" | jq
```

**Query params:** `limit` (default 100, max 500)

---

## 5. Incident Endpoints

### GET /api/incidents — List semua incident

```bash
curl -s http://localhost:8001/api/incidents | jq
```

### POST /api/incidents — Buat incident baru

```bash
curl -s -X POST http://localhost:8001/api/incidents \
  -H "Content-Type: application/json" \
  -d '{
    "bus_id": 5,
    "type": "breakdown",
    "severity": "high",
    "description": "Engine overheat pada bus B 1005 UPN di Jl. Sudirman"
  }' | jq
```

**Expected:** `201 Created`
- Bus status otomatis berubah menjadi `incident`.
- Event `fleet.incident.created` dan `bus.status.updated` dikirim ke RabbitMQ.

**Enum `type`:** `breakdown`, `accident`, `traffic`, `maintenance`, `anomaly`, `other`

**Enum `severity`:** `low`, `medium`, `high`, `critical`

### GET /api/incidents/{id} — Detail incident

```bash
curl -s http://localhost:8001/api/incidents/1 | jq
```

### PATCH /api/incidents/{id} — Update incident

```bash
curl -s -X PATCH http://localhost:8001/api/incidents/1 \
  -H "Content-Type: application/json" \
  -d '{
    "severity": "critical",
    "description": "Engine overheat parah, bus ditarik ke depo"
  }' | jq
```

### PATCH /api/incidents/{id}/resolve — Resolve incident

```bash
curl -s -X PATCH http://localhost:8001/api/incidents/1/resolve | jq
```

**Expected:** `200`
- `resolved_at` diisi timestamp sekarang.
- Jika bus tidak punya incident aktif lain, status bus dikembalikan ke `active`.
- Event `bus.status.updated` dikirim ke RabbitMQ.

### DELETE /api/incidents/{id} — Hapus incident

```bash
curl -s -X DELETE http://localhost:8001/api/incidents/1 | jq
```

---

## 6. Error Response Examples

### 404 — Resource not found

```bash
curl -s http://localhost:8001/api/buses/999 | jq
```

```json
{
  "status": "error",
  "code": 404,
  "message": "Bus #999 not found",
  "errors": null
}
```

### 400 — Validation error

```bash
curl -s -X POST http://localhost:8001/api/buses \
  -H "Content-Type: application/json" \
  -d '{}' | jq
```

```json
{
  "status": "error",
  "code": 400,
  "message": "Validation failed",
  "errors": {
    "plate_number": "The plate_number field is required."
  }
}
```

### 503 — Database down

```json
{
  "status": "error",
  "code": 503,
  "message": "Database unavailable",
  "data": {
    "service": "fleet-service",
    "status": "degraded",
    "checks": { "database": { "connected": false } }
  }
}
```

---

## 7. RabbitMQ Events Published

| Routing Key | Trigger | Payload |
|---|---|---|
| `gps.update` | POST /api/gps | `{ event, bus_id, route_id, lat, lng, speed_kmh, heading, passenger_count, engine_temp, recorded_at }` |
| `fleet.incident.created` | POST /api/incidents | `{ event, incident_id, bus_id, type, severity, reported_at }` |
| `bus.status.updated` | PATCH bus status / incident create / incident resolve | `{ event, bus_id, old_status, new_status, timestamp }` |

Exchange: `city.events` (topic, durable)
