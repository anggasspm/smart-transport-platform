const express = require('express');
const app = express();
const PORT = 3000;

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

app.listen(PORT, () => {
console.log('API Gateway running on port ${PORT}');
});