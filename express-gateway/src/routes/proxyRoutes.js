const { createProxyMiddleware } = require('http-proxy-middleware');
const express = require('express');
const router = express.Router();
const iotOrAuthMiddleware = require('../middleware/iotOrAuthMiddleware');

const proxy = (target, pathRewrite = {}) =>
    createProxyMiddleware({ target, changeOrigin: true, pathRewrite });

// Passenger Service
router.use('/api/passengers',
    iotOrAuthMiddleware,
    proxy('http://localhost:8000')
);

router.use('/api/tickets',
    iotOrAuthMiddleware,
    proxy('http://localhost:8000')
);

// Fleet Service
router.use('/api/buses',
    iotOrAuthMiddleware,
    proxy('http://localhost:8001')
);

router.use('/api/routes',
    iotOrAuthMiddleware,
    proxy('http://localhost:8001')
);

router.use('/api/gps',
    iotOrAuthMiddleware,
    proxy('http://localhost:8001')
);

// Stop Service
router.use('/api/stops',
    iotOrAuthMiddleware,
    proxy('http://localhost:8002')
);

// IoT
router.use('/iot/gps',
    iotOrAuthMiddleware,
    proxy('http://localhost:8001', { '^/iot/gps': '/api/gps' })
);

router.use('/iot/passengers',
    iotOrAuthMiddleware,
    proxy('http://localhost:8002', { '^/iot/passengers': '/api/stops/passenger-count' })
);

// ML Service
router.use('/predict',
    proxy('http://localhost:5000')
);

module.exports = router;