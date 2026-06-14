const { createProxyMiddleware } = require('http-proxy-middleware');
const express = require('express');
const router = express.Router();

router.use('/api/passengers', createProxyMiddleware({ 
    target: 'http://localhost:8000', 
    changeOrigin: true 
}));

router.use('/api/buses', createProxyMiddleware({ 
    target: 'http://localhost:8001', 
    changeOrigin: true 
}));

router.use('/api/stops', createProxyMiddleware({ 
    target: 'http://localhost:8002', 
    changeOrigin: true 
}));

router.use('/predict', createProxyMiddleware({ 
    target: 'http://localhost:5000', 
    changeOrigin: true 
}));

module.exports = router;