const axios = require('axios');
const qs = require('querystring');

const optionalAuthMiddleware = async (req, res, next) => {
    const authHeader = req.headers['authorization'];
    if (!authHeader) {
        return next();
    }
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
            req.headers['x-user-role'] = response.data.role ?? 'passenger';
        }
    } catch (err) {}
    return next();
};

module.exports = optionalAuthMiddleware;
