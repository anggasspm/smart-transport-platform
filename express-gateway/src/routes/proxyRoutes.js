const { createProxyMiddleware } = require('http-proxy-middleware');
const express = require('express');
const router = express.Router();
const authMiddleware = require('../middleware/authMiddleware');

const proxy = (target, pathRewrite = {}) =>
    createProxyMiddleware({ target, changeOrigin: true, pathRewrite });

// Register (Publik)
router.post('/api/passengers', proxy('http://php-passenger:8000'));

// Passenger Service
router.use('/api/passengers', authMiddleware, proxy('http://php-passenger:8000'));
router.use('/api/tickets', authMiddleware, proxy('http://php-passenger:8000'));
router.use('/api/notifications', authMiddleware, proxy('http://php-notification:8000'));

// Fleet Service
router.use('/api/buses', authMiddleware, proxy('http://php-fleet:8001'));
router.use('/api/routes', authMiddleware, proxy('http://php-fleet:8001'));
router.use('/api/gps', authMiddleware, proxy('http://php-fleet:8001'));

// Stop Service
router.use('/api/stops', authMiddleware, proxy('http://php-stop:8002'));

// IoT
router.use('/iot/gps', authMiddleware, proxy('http://php-fleet:8001', { '^/iot/gps': '/api/gps' }));
router.use('/iot/passengers', authMiddleware, proxy('http://php-stop:8002', { '^/iot/passengers': '/api/stops/passenger-count' }));

// ML
router.use('/predict', authMiddleware, proxy('http://python-ml:5000'));

module.exports = router;