const express = require('express');
const app = express();
const PORT = 3002;

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

app.listen(PORT, () => {
    console.log(`OAuth Server running on port ${PORT}`);
});