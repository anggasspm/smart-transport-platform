<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    /**
     * Base Site URL
     */
    public string $baseURL = 'http://localhost:8001/';

    /**
     * Allowed Hostnames
     * @var list<string>
     */
    public array $allowedHostnames = [];

    /**
     * Index File - kosongkan agar URL bersih
     */
    public string $indexPage = '';

    /**
     * URI Protocol
     */
    public string $uriProtocol = 'REQUEST_URI';

    /**
     * Allowed URL Characters
     */
    public string $permittedURIChars = 'a-z 0-9~%.:_\-';

    /**
     * Default Locale
     */
    public string $defaultLocale = 'en';

    /**
     * Negotiate Locale
     */
    public bool $negotiateLocale = false;

    /**
     * Supported Locales
     * @var list<string>
     */
    public array $supportedLocales = ['en'];

    /**
     * Application Timezone
     */
    public string $appTimezone = 'UTC';

    /**
     * Default Character Set
     */
    public string $charset = 'UTF-8';

    /**
     * Force Global Secure Requests
     */
    public bool $forceGlobalSecureRequests = false;

    /**
     * Reverse Proxy IPs
     * @var array<string, string>
     */
    public array $proxyIPs = [];

    /**
     * Content Security Policy
     */
    public bool $CSPEnabled = false;
}
