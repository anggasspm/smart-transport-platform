<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Logger Configuration
 */
class Logger extends BaseConfig
{
    /**
     * Log threshold
     * 0 = disable, 1 = error, 2 = debug, 3 = info, 4 = all
     */
    public int $threshold = 4;

    /**
     * Date format untuk log
     */
    public string $dateFormat = 'Y-m-d H:i:s';

    /**
     * Handler yang aktif
     * @var array<string, array<string, mixed>>
     */
    public array $handlers = [
        'CodeIgniter\Log\Handlers\FileHandler' => [
            'handles' => ['critical', 'alert', 'emergency', 'debug', 'error', 'info', 'notice', 'warning'],
        ],
    ];
}
