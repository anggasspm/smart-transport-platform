const express = require('express');
const app = express();
const PORT = 3000;
const proxyRoutes = require('./routes/proxyRoutes');
const apiLimiter = require('./middleware/rateLimiter');
const client = require('prom-client');
const axios = require('axios');
const commandRoutes = require('./routes/commandRoutes');

app.use(express.json());

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

//app.use(apiLimiter);

app.get('/health', async (req, res) => {
    const services = [
        { name: 'oauth_server', url: 'http://oauth-server:3002/health' },
        { name: 'php_passenger', url: 'http://php-passenger:8000/health' },
        { name: 'php_fleet', url: 'http://php-fleet:8001/health' },
        { name: 'php_stop', url: 'http://php-stop:8002/health' }
    ];

    const dependencies = {};

    const checkPromises = services.map(async (service) => {
        try {
            await axios.get(service.url, { timeout: 3000 });
            dependencies[service.name] = "UP";
        } catch (error) {
            dependencies[service.name] = "DOWN";
        }
    });

    await Promise.all(checkPromises);

    const allHealthy = Object.values(dependencies).every(s => s === "UP");

    res.status(allHealthy ? 200 : 207).json({ 
        status: allHealthy ? "success" : "degraded",
        code: allHealthy ? 200 : 207,
        data: dependencies,
        message: "Gateway and dependencies checked",
        timestamp: new Date().toISOString(),
        service: "api-gateway"
    });
});

app.get('/metrics', async (req, res) => {
    res.set('Content-Type', client.register.contentType);
    res.end(await client.register.metrics());
});

app.use('/', commandRoutes);
app.use('/', proxyRoutes);

app.listen(PORT, '0.0.0.0', () => {
    console.log(`API Gateway running on port ${PORT}`); 
});