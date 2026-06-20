const express = require('express');
const app = express();
const PORT = 3000;
const proxyRoutes = require('./routes/proxyRoutes');
const apiLimiter = require('./middleware/rateLimiter');
const client = require('prom-client');

const collectDefaultMetrics = client.collectDefaultMetrics;

collectDefaultMetrics({ prefix: 'gateway_' });

const httpRequestsTotal = new client.Counter({
    name: 'http_requests_total',
    help: 'Total HTTP requests',
    labelNames: ['method', 'path', 'status', 'job']
});

const httpRequestDuration = new client.Histogram({
    name: 'http_request_duration_seconds',
    help: 'HTTP request duration in seconds',
    labelNames: ['method', 'path', 'status', 'job'],
    buckets: [0.05, 0.1, 0.3, 0.5, 1, 2, 5]
});

app.use((req, res, next) => {
    const end = httpRequestDuration.startTimer();
    res.on('finish', () => {
        httpRequestsTotal.inc({ method: req.method, path: req.path, status: res.statusCode, job: 'api-gateway' });
        end({ method: req.method, path: req.path, status: res.statusCode, job: 'api-gateway' });
    });
    next();
});

app.use(apiLimiter);

app.get('/health', (req, res) => {
    res.status(200).json({
        status: "success",
        code: 200,
        data: {
            oauth_server: "checking...",
            fleet_service: "checking...",
            passenger_service: "checking..."
        },
        message: "Gateway is running",
        timestamp: new Date().toISOString(),
        service: "api-gateway"
    });
});

app.get('/metrics', async (req, res) => {
    res.set('Content-Type', client.register.contentType);
    res.end(await client.register.metrics());
});

app.use('/', proxyRoutes);

app.listen(PORT, '0.0.0.0', () => {
    console.log(`API Gateway running on port ${PORT}`); 
});