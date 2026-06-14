<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Paths Configuration
 *
 * Path ke folder system, app, dan writable.
 */
class Paths extends BaseConfig
{
    /**
     * Path ke folder system/ CodeIgniter 4
     * Saat menggunakan package via Composer:
     * vendor/codeigniter4/framework/system
     */
    public string $systemDirectory = __DIR__ . '/../../vendor/codeigniter4/framework/system';

    /**
     * Path ke folder app/
     */
    public string $appDirectory = __DIR__ . '/..';

    /**
     * Path ke folder writable/ (cache, logs, session)
     */
    public string $writableDirectory = __DIR__ . '/../../writable';
}
