<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Autoload Configuration
 */
class Autoload extends BaseConfig
{
    /**
     * PSR-4 namespaces dan path-nya.
     * Namespace 'App' wajib ada untuk mengarah ke app/
     *
     * @var array<string, list<string>|string>
     */
    public array $psr4 = [
        APP_NAMESPACE => APPPATH,
        'Config'      => APPPATH . 'Config',
    ];

    /**
     * Class Map - untuk class yang tidak ikut PSR-4
     *
     * @var array<string, string>
     */
    public array $classmap = [];

    /**
     * Files yang diload secara global (helper, dsb)
     *
     * @var list<string>
     */
    public array $files = [];

    /**
     * Helper yang diload otomatis di semua request.
     * fleet_response menyediakan fleet_success(), fleet_error(), dll.
     *
     * @var list<string>
     */
    public array $helpers = ['fleet_response', 'url'];
}
