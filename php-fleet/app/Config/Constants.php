<?php

/*
 * ---------------------------------------------------------------
 *  Application Constants
 * ---------------------------------------------------------------
 */

defined('ENVIRONMENT') || define('ENVIRONMENT', env('CI_ENVIRONMENT', 'production'));

/*
 * ---------------------------------------------------------------
 *  Setup Path Constants
 * ---------------------------------------------------------------
 */

// Ensure that FCPATH and ROOTPATH are defined before this file loads.
// They are normally set in public/index.php or spark.

// The path to the "app" folder
defined('APPPATH') || define('APPPATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);

// The path to the "system" folder
defined('SYSTEMPATH') || define('SYSTEMPATH',
    realpath(APPPATH . '../vendor/codeigniter4/framework/system') . DIRECTORY_SEPARATOR
);

// The path to the "writable" folder
defined('WRITEPATH') || define('WRITEPATH', realpath(APPPATH . '../writable') . DIRECTORY_SEPARATOR);

// The path to the tests directory
defined('TESTPATH') || define('TESTPATH', realpath(APPPATH . '../tests') . DIRECTORY_SEPARATOR);

/*
 * ---------------------------------------------------------------
 *  App Namespace
 * ---------------------------------------------------------------
 */
defined('APP_NAMESPACE') || define('APP_NAMESPACE', 'App');

/*
 * ---------------------------------------------------------------
 *  Composer Path
 * ---------------------------------------------------------------
 */
defined('COMPOSER_PATH') || define('COMPOSER_PATH', realpath(APPPATH . '../vendor/autoload.php'));
