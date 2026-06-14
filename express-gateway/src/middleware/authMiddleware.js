const axios = require('axios');
const { formatResponse } = require('../utils/responseFormatter');

const authMiddleware = async (req, res, next) => {
    const authHeader = req.headers['authorization'];

    if (!authHeader) {
        return formatResponse(res, 'error', 401, null, 'Token tidak ditemukan', 'api-gateway');
    }

    try {
        const response = await axios.post('http://localhost:3002/oauth/introspect', {
            token: authHeader.split(' ')[1]
        });

        if (response.data.active) {
            next();
        } else {
            formatResponse(res, 'error', 401, null, 'Token tidak valid', 'api-gateway');
        }
    } catch (err) {
        formatResponse(res, 'error', 500, null, 'Gagal validasi token ke Auth Server', 'api-gateway');
    }
};

module.exports = authMiddleware;