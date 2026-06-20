const { createProxyMiddleware } = require('http-proxy-middleware');
const express = require('express');
const router = express.Router();
const authMiddleware = require('../middleware/authMiddleware');

// ---------------------------------------------------------------------------
// Service URLs — resolved from env vars with Docker-network defaults
// ---------------------------------------------------------------------------
const PASSENGER_SERVICE_URL = process.env.PASSENGER_SERVICE_URL || 'http://php-passenger:8000';
const FLEET_SERVICE_URL     = process.env.FLEET_SERVICE_URL     || 'http://php-fleet:8001';
const STOP_SERVICE_URL      = process.env.STOP_SERVICE_URL      || 'http://php-stop:8002';
const PYTHON_ML_URL         = process.env.PYTHON_ML_URL         || 'http://python-ml:5000';

// ---------------------------------------------------------------------------
// Helper: create proxy with auth + full-path preservation
// Using pathFilter so Express does NOT strip the mount prefix from req.url.
// ---------------------------------------------------------------------------
function authedProxy(pathPrefix, targetUrl) {
    return [
        authMiddleware,
        createProxyMiddleware({
            target: targetUrl,
            changeOrigin: true,
            pathFilter: pathPrefix,
        }),
    ];
}

// ---------------------------------------------------------------------------
// Passenger Service
// ---------------------------------------------------------------------------
router.use(...authedProxy('/api/passengers', PASSENGER_SERVICE_URL));

// ---------------------------------------------------------------------------
// Fleet Service — buses, routes, gps, incidents
// ---------------------------------------------------------------------------
router.use(...authedProxy('/api/buses',      FLEET_SERVICE_URL));
router.use(...authedProxy('/api/routes',     FLEET_SERVICE_URL));
router.use(...authedProxy('/api/gps',        FLEET_SERVICE_URL));
router.use(...authedProxy('/api/incidents',  FLEET_SERVICE_URL));

// ---------------------------------------------------------------------------
// Stop Service
// ---------------------------------------------------------------------------
router.use(...authedProxy('/api/stops', STOP_SERVICE_URL));

// ---------------------------------------------------------------------------
// ML / Prediction Service (no auth required)
// ---------------------------------------------------------------------------
router.use(createProxyMiddleware({
    target: PYTHON_ML_URL,
    changeOrigin: true,
    pathFilter: '/predict',
}));

module.exports = router;