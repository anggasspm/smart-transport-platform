const authMiddleware = require('./authMiddleware');

const IOT_SECRET_KEY = process.env.IOT_SECRET_KEY || 'iot-secret-key';

function iotOrAuthMiddleware(req, res, next) {
    const iotKey = req.headers['x-iot-key'];

    if (iotKey) {
        if (iotKey === IOT_SECRET_KEY) {
            return next();
        }

        return res.status(401).json({
            status: 'error',
            code: 401,
            message: 'Invalid IoT key',
            service: 'gateway'
        });
    }

    return authMiddleware(req, res, next);
}

module.exports = iotOrAuthMiddleware;