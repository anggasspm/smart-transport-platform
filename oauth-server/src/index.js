const express = require('express');
const OAuth2Server = require('oauth2-server');
const model = require('./model');
const app = express();
const PORT = 3002;

const client = require('prom-client');
const collectDefaultMetrics = client.collectDefaultMetrics;
collectDefaultMetrics({ prefix: 'oauth_' });

const httpRequestsTotal = new client.Counter({
    name: 'oauth_http_requests_total',
    help: 'Total HTTP requests to OAuth server',
    labelNames: ['method', 'path', 'status', 'job']
});

const httpRequestDuration = new client.Histogram({
    name: 'oauth_http_request_duration_seconds',
    help: 'HTTP request duration in seconds',
    labelNames: ['method', 'path', 'job'],
    buckets: [0.05, 0.1, 0.3, 0.5, 1, 2, 5]
});

const oauth = new OAuth2Server({
    model: model,
    grants: ['password', 'client_credentials', 'refresh_token'],
    debug: true
});

app.use(express.json());
app.use(express.urlencoded({ extended: false }));

app.use((req, res, next) => {
    const end = httpRequestDuration.startTimer();
    res.on('finish', () => {
        httpRequestsTotal.inc({ method: req.method, path: req.path, status: res.statusCode, job: 'oauth-server' });
        end({ method: req.method, path: req.path, job: 'oauth-server' });
    });
    next();
});

app.get('/health', (req, res) => {
    res.status(200).json({
        status: "success",
        code: 200,
        data: null,
        message: "OAuth Server is healthy",
        timestamp: new Date().toISOString(),
        service: "oauth-server"
    });
});

app.get('/metrics', async (req, res) => {
    res.set('Content-Type', client.register.contentType);
    res.end(await client.register.metrics());
});

app.post('/oauth/token', async (req, res) => {
    const request = new OAuth2Server.Request(req);
    const response = new OAuth2Server.Response(res);
    try {
        const token = await oauth.token(request, response);
        res.json(token);
    } catch (err) {
        res.status(err.code || 500).json({
            status: "error",
            code: err.code || 500,
            message: err.message,
            service: "oauth-server"
        });
    }
});

app.post('/oauth/revoke', async (req, res) => {
    const token = req.body.token;
    if (!token) {
        return res.status(400).json({ error: "Token is required" });
    }
    await model.revokeToken({ accessToken: token });
    res.json({ message: "Token successfully revoked" });
});

app.post('/oauth/introspect', async (req, res) => {
    const tokenFromBody = req.body && req.body.token;
    const fakeReq = tokenFromBody
        ? { headers: { authorization: 'Bearer ' + tokenFromBody }, query: {}, method: req.method }
        : req;

    const request = new OAuth2Server.Request(fakeReq);
    const response = new OAuth2Server.Response(res);

    try {
        const tokenInfo = await oauth.authenticate(request, response);
        res.json({
            active: true,
            user_id: tokenInfo.user ? tokenInfo.user.id : undefined,
            role: tokenInfo.user ? tokenInfo.user.role : undefined,
            client_id: tokenInfo.client ? tokenInfo.client.id : undefined,
            scope: tokenInfo.scope,
            exp: tokenInfo.accessTokenExpiresAt
                ? Math.floor(new Date(tokenInfo.accessTokenExpiresAt).getTime() / 1000)
                : undefined
        });
    } catch (err) {
        res.status(200).json({ active: false, message: err.message });
    }
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`OAuth Server running on port ${PORT}`);
});