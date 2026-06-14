<?php

/*
 *---------------------------------------------------------------
 * APPLICATION ENVIRONMENT
 *---------------------------------------------------------------
 *
 * You can load different configurations depending on your
 * current environment. Setting the environment also influences
 * things like logging and error reporting.
 *
 * This can be set to anything, but default usage is:
 *
 *     development
 *     testing
 *     production
 */

/*
 *---------------------------------------------------------------
 * BOOTSTRAP THE APPLICATION
 *---------------------------------------------------------------
 * This process sets up the path constants, loads and registers
 * our autoloader, along with Composer's, loads our Routes and
 * brings in the CodeIgniter class.
 */

// Ensure the current directory is pointing to the front controller's directory
if (getcwd() . DIRECTORY_SEPARATOR !== __DIR__ . DIRECTORY_SEPARATOR) {
    chdir(__DIR__);
}

/*
 *---------------------------------------------------------------
 * DEFINE PATHS
 *---------------------------------------------------------------
 */

// The path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// The path to the project root
define('ROOTPATH', realpath(FCPATH . '..') . DIRECTORY_SEPARATOR);

/*
 *---------------------------------------------------------------
 * LOAD BOOTSTRAP FILE
 *---------------------------------------------------------------
 */

// Load the framework bootstrap file
require realpath(ROOTPATH . 'vendor/codeigniter4/framework/system/bootstrap.php') ?? realpath(ROOTPATH . 'system/bootstrap.php');

/*
 *---------------------------------------------------------------
 * LAUNCH THE APPLICATION
 *---------------------------------------------------------------
 */

$app = Config\Services::codeigniter();
$app->initialize();
$context = is_cli() ? 'php-cli' : 'web';
$app->setContext($context);
$app->run();
