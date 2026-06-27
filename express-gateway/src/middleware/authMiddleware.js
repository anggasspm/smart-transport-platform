const axios = require('axios');
const qs = require('querystring');
const { formatResponse } = require('../utils/responseFormatter');

const authMiddleware = async (req, res, next) => {
    const authHeader = req.headers['authorization'];
    console.log("==== AUTH ====");
console.log(req.method, req.originalUrl);
console.log(req.headers);

    if (!authHeader) {
        console.log("NO AUTH HEADER");
        return formatResponse(
            res,
            'error',
            401,
            null,
            'Token tidak ditemukan',
            'api-gateway'
        );
    }

    console.log("AUTH HEADER:", authHeader);
    
    try {
        const token = authHeader.split(' ')[1];
        const response = await axios.post(
            'http://oauth-server:3002/oauth/introspect',
            qs.stringify({ token }),
            { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } }
        );
        
        if (response.data.active) {
            if (response.data.user_id) {
                req.headers['x-user-id'] = response.data.user_id.toString();
            }
            
            return next();
        } else {
            return formatResponse(res, 'error', 401, null, 'Token tidak valid', 'api-gateway');
        }
    } catch (err) {
        return formatResponse(res, 'error', 500, null, 'Gagal validasi token ke Auth Server', 'api-gateway');
    }
};

module.exports = authMiddleware;
