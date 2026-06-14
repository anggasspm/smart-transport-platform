<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Routing extends BaseConfig
{
    /**
     * Default namespace untuk controller
     */
    public string $defaultNamespace = 'App\Controllers';

    /**
     * Default controller jika route tidak match
     */
    public string $defaultController = 'Home';

    /**
     * Default method yang dipanggil pada controller
     */
    public string $defaultMethod = 'index';

    /**
     * Auto route (CI4 style) - dimatikan agar route eksplisit saja
     */
    public bool $autoRoute = false;

    /**
     * Override 404 handler
     */
    public ?string $override404 = null;

    /**
     * Prioritize route resolution (regex dulu)
     */
    public bool $prioritize = false;

    /**
     * Module routing
     * @var array<string, string>
     */
    public array $moduleRoutes = [];
}
