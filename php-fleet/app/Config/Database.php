<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 *
 * Mendukung dua format env var:
 * - Docker Compose style: DB_HOST, DB_NAME, DB_USER, DB_PASS
 * - CI4 .env style: database.default.hostname, dll (di-handle otomatis CI4)
 */
class Database extends Config
{
    /**
     * The directory that holds the Migrations and Seeds directories.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Default connection group
     */
    public string $defaultGroup = 'default';

    /**
     * @var array<string, mixed>
     */
    public array $default = [
        'DSN'      => '',
        'hostname' => 'mysql',
        'username' => 'root',
        'password' => '',
        'database' => 'smarttransport',
        'DBDriver' => 'MySQLi',
        'DBPrefix' => '',
        'pConnect' => false,
        'DBDebug'  => false,
        'charset'  => 'utf8mb4',
        'DBCollat' => 'utf8mb4_general_ci',
        'swapPre'  => '',
        'encrypt'  => false,
        'compress' => false,
        'strictOn' => false,
        'failover' => [],
        'port'     => 3306,
    ];

    public function __construct()
    {
        parent::__construct();

        // Baca env var Docker-style (diset di docker-compose.yml)
        // Lebih prioritas daripada default di atas
        if ($host = getenv('DB_HOST')) {
            $this->default['hostname'] = $host;
        }
        if ($name = getenv('DB_NAME')) {
            $this->default['database'] = $name;
        }
        if ($user = getenv('DB_USER')) {
            $this->default['username'] = $user;
        }
        if ($pass = getenv('DB_PASS')) {
            $this->default['password'] = $pass;
        }
        if ($port = getenv('DB_PORT')) {
            $this->default['port'] = (int) $port;
        }

        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }
    }
}
