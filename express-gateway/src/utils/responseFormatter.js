const formatResponse = (res, status, code, data, message, service = 'api-gateway') => {
    return res.status(code).json({
        status: status,
        code: code,
        data: data,
        message: message,
        timestamp: new Date().toISOString(),
        service: service
    });
};

module.exports = { formatResponse };