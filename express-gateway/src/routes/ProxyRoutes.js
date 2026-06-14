const { createProxyMiddleware } = require('http-proxy-middleware');
const express = require('express');
const router = express.Router();
const authMiddleware = require('../middleware/authMiddleware');

router.use('/api/passengers', authMiddleware, createProxyMiddleware({ 
    target: 'http://localhost:8000', 
    changeOrigin: true 
}));

router.use('/api/buses', authMiddleware, createProxyMiddleware({ 
    target: 'http://localhost:8001', 
    changeOrigin: true 
}));

router.use('/api/stops', authMiddleware, createProxyMiddleware({ 
    target: 'http://localhost:8002', 
    changeOrigin: true 
}));

router.use('/predict', createProxyMiddleware({ 
    target: 'http://localhost:5000', 
    changeOrigin: true 
}));

module.exports = router;