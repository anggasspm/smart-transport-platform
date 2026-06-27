const mysql = require('mysql2/promise');

const pool = mysql.createPool({
    host: process.env.DB_HOST || 'mysql',
    port: process.env.DB_PORT || 3306,
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASS || 'SmartTransport2025!',
    database: process.env.DB_NAME || 'smarttransport',
    waitForConnections: true,
    connectionLimit: 10
});

module.exports = pool;