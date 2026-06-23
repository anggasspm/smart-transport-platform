const rateLimit = require('express-rate-limit');
const { formatResponse } = require('../utils/responseFormatter');

const apiLimiter = rateLimit({
    windowMs: 15 * 60 * 1000,
    max: 10000,
    handler: (req, res) => {
        formatResponse(res, 'error', 429, null, 'Terlalu banyak permintaan, coba lagi nanti.', 'api-gateway');
    }
});

module.exports = apiLimiter;