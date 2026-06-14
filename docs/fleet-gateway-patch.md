# Fleet Service — API Gateway Proxy Configuration

> **Dokumen ini untuk tim API Gateway.**
> Fleet Service berjalan di container `php-fleet` port `8001`.

---

## 1. Endpoint yang HARUS Di-Proxy

Gateway harus forward semua request berikut ke Fleet Service:

| Path Pattern | Target |
|---|---|
| `/api/buses/*` | `http://php-fleet:8001` |
| `/api/routes/*` | `http://php-fleet:8001` |
| `/api/gps/*` | `http://php-fleet:8001` |
| `/api/incidents/*` | `http://php-fleet:8001` |

> [!CAUTION]
> Gunakan hostname Docker internal `http://php-fleet:8001`, **BUKAN** `http://localhost:8001`.
> `localhost` di dalam container Gateway merujuk ke container Gateway itu sendiri.

---

## 2. Contoh Konfigurasi Express.js + http-proxy-middleware

### Install dependency

```bash
npm install http-proxy-middleware
```

### Konfigurasi proxy

```javascript
// gateway/src/proxy/fleet.js
const { createProxyMiddleware } = require('http-proxy-middleware');

const FLEET_SERVICE_URL = process.env.FLEET_SERVICE_URL || 'http://php-fleet:8001';

const fleetProxy = createProxyMiddleware({
  target: FLEET_SERVICE_URL,
  changeOrigin: true,
  // path tetap diteruskan as-is ke fleet service
  pathRewrite: {},
  // Timeout 30 detik
  proxyTimeout: 30000,
  onError: (err, req, res) => {
    console.error(`[Gateway] Fleet proxy error: ${err.message}`);
    res.status(502).json({
      status: 'error',
      code: 502,
      message: 'Fleet Service unavailable',
    });
  },
});

module.exports = fleetProxy;
```

### Register di Express app

```javascript
// gateway/src/app.js
const express = require('express');
const fleetProxy = require('./proxy/fleet');

const app = express();

// Fleet Service routes
app.use('/api/buses',     fleetProxy);
app.use('/api/routes',    fleetProxy);
app.use('/api/gps',       fleetProxy);
app.use('/api/incidents', fleetProxy);

// ... other services ...
```

### Environment variable di docker-compose.yml

```yaml
api-gateway:
  environment:
    FLEET_SERVICE_URL: http://php-fleet:8001
```

> Variabel `FLEET_SERVICE_URL` sudah ada di `docker-compose.yml` saat ini.

---

## 3. Health Check Endpoint

Gateway bisa memonitor Fleet Service health:

```
GET http://php-fleet:8001/health
```

Response `200` = service up. Response `503` = database down.

Bisa digunakan sebagai health check di gateway sebelum meneruskan traffic.

---

## 4. CORS & Headers

Fleet Service tidak menangani CORS — ini tanggung jawab Gateway. Pastikan Gateway menambahkan:

```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization
```

---

## 5. Auth/JWT

Fleet Service **tidak memverifikasi JWT**. Semua autentikasi dan otorisasi harus dilakukan di Gateway sebelum request diteruskan ke Fleet Service.

---

## 6. Checklist Verifikasi Gateway

Setelah konfigurasi, verifikasi:

- [ ] `curl http://localhost:3000/api/buses` → forward ke `http://php-fleet:8001/api/buses` → 200 + data JSON
- [ ] `curl http://localhost:3000/api/routes` → 200 + 10 routes
- [ ] `curl -X POST http://localhost:3000/api/gps -H "Content-Type: application/json" -d '{"bus_id":1,"route_id":1,"lat":-6.3,"lng":106.8,"speed_kmh":30,"heading":90,"timestamp":1718340000}'` → 201
- [ ] `curl http://localhost:3000/api/incidents` → 200
- [ ] Response headers mengandung CORS headers yang sesuai
- [ ] Request dengan JWT invalid ditolak di gateway (tidak sampai ke Fleet Service)
- [ ] Error 502 muncul jika Fleet Service down

---

## 7. Catatan Docker Networking

Semua service berada di network `smarttransport-net`. Komunikasi antar container menggunakan **service name** sebagai hostname:

| Service | Hostname Internal | Port |
|---|---|---|
| Fleet Service | `php-fleet` | 8001 |
| Passenger Service | `php-passenger` | 8000 |
| Stop Service | `php-stop` | 8002 |
| MySQL | `mysql` | 3306 |
| RabbitMQ | `rabbitmq` | 5672 |
| API Gateway | `api-gateway` | 3000 |
