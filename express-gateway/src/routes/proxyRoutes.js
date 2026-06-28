const { createProxyMiddleware, fixRequestBody } = require('http-proxy-middleware');
const express = require('express');
const router = express.Router();
const authMiddleware = require('../middleware/authMiddleware');
const optionalAuthMiddleware = require('../middleware/optionalAuthMiddleware');

const proxy = (target, pathRewrite = {}) =>
    createProxyMiddleware({
        target,
        changeOrigin: true,
        pathRewrite,
        on: { proxyReq: fixRequestBody },
    });

// Register (Publik)
router.post('/api/passengers', proxy('http://php-passenger:8000'));

router.use('/api/passengers/count', proxy('http://php-stop:8002'));

// Passenger Service
router.use('/api/passengers', authMiddleware, proxy('http://php-passenger:8000'));
router.use('/api/tickets', authMiddleware, proxy('http://php-passenger:8000'));
router.use('/api/notifications', authMiddleware, proxy('http://php-passenger:8000'));

// Fleet Service — optional auth (GET publik, POST/PATCH/DELETE butuh role admin)
router.use('/api/buses',     optionalAuthMiddleware, proxy('http://php-fleet:8001'));
router.use('/api/routes',    optionalAuthMiddleware, proxy('http://php-fleet:8001'));
router.use('/api/gps',       optionalAuthMiddleware, proxy('http://php-fleet:8001'));
router.use('/api/incidents', optionalAuthMiddleware, proxy('http://php-fleet:8001'));

// Stop Service
router.use('/api/stops', proxy('http://php-stop:8002'));
router.use('/api/alerts', proxy('http://php-stop:8002'));

// IoT
router.use('/iot/gps', authMiddleware, proxy('http://php-fleet:8001', { '^/iot/gps': '/api/gps' }));
router.use('/iot/passengers', authMiddleware, proxy('http://php-stop:8002', { '^/iot/passengers': '/api/stops/passenger-count' }));

// ML
router.use('/predict', authMiddleware, proxy('http://python-ml:5000'));
router.use('/detect', authMiddleware, proxy('http://python-ml:5000'));
router.use('/delay', authMiddleware, proxy('http://python-ml:5000'));

module.exports = router;