const express = require('express');
const router = express.Router();
const authMiddleware = require('../middleware/authMiddleware');
const { publishBusCommand } = require('../services/rabbitmqPublisher');

router.post('/api/buses/:id/command', express.json(), authMiddleware, async (req, res) => {
    const busId = parseInt(req.params.id, 10);
    const { command } = req.body;

    if (command !== 'resume_service') {
        return res.status(400).json({
            status: 'error', code: 400, data: null,
            message: `Command '${command}' tidak didukung. Hanya 'resume_service'.`,
            timestamp: new Date().toISOString(), service: 'api-gateway'
        });
    }

    try {
        const payload = await publishBusCommand(busId, command);
        res.status(202).json({
            status: 'success', code: 202, data: payload,
            message: 'Command diterima dan dikirim ke bus',
            timestamp: new Date().toISOString(), service: 'api-gateway'
        });
    } catch (err) {
        console.error('[commandRoutes] Gagal publish ke RabbitMQ:', err.message);
        res.status(502).json({
            status: 'error', code: 502, data: null,
            message: 'Gagal mengirim command ke RabbitMQ',
            timestamp: new Date().toISOString(), service: 'api-gateway'
        });
    }
});

module.exports = router;