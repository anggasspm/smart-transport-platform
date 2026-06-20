const { createProxyMiddleware } = require('http-proxy-middleware');
const express = require('express');
const router = express.Router();
const iotOrAuthMiddleware = require('../middleware/iotOrAuthMiddleware');

const proxy = (target, pathRewrite = {}) =>
    createProxyMiddleware({ target, changeOrigin: true, pathRewrite });

// Passenger Service
router.use('/api/passengers', iotOrAuthMiddleware, proxy('http://php-passenger:8000'));
router.use('/api/tickets', iotOrAuthMiddleware, proxy('http://php-passenger:8000'));

// Fleet Service
router.use('/api/buses', iotOrAuthMiddleware, proxy('http://php-fleet:8001'));
router.use('/api/routes', iotOrAuthMiddleware, proxy('http://php-fleet:8001'));
router.use('/api/gps', iotOrAuthMiddleware, proxy('http://php-fleet:8001'));

// Stop Service
router.use('/api/stops', iotOrAuthMiddleware, proxy('http://php-stop:8002'));

// IoT
router.use('/iot/gps', iotOrAuthMiddleware, proxy('http://php-fleet:8001', { '^/iot/gps': '/api/gps' }));
router.use('/iot/passengers', iotOrAuthMiddleware, proxy('http://php-stop:8002', { '^/iot/passengers': '/api/stops/passenger-count' }));

// ML
router.use('/predict', proxy('http://python-ml:5000'));

module.exports = router;