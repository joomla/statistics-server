<?php

/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

// Application constants
\define('APPROOT', \dirname(__DIR__));

// Ensure we've initialized Composer
if (!file_exists(APPROOT . '/vendor/autoload.php')) {
    header('HTTP/1.1 500 Internal Server Error', null, 500);
    header('Content-Type: application/json; charset=utf-8');

    echo \json_encode(
        [
            'code'    => 500,
            'message' => 'Composer is not set up properly, please run "composer install".',
            'error'   => true,
        ]
    );

    exit;
}

require APPROOT . '/vendor/autoload.php';

try {
    (new \Joomla\StatsServer\Kernel\WebKernel())->run();
} catch (\Throwable $throwable) {
    error_log($throwable);

    if (!headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error', null, 500);
        header('Content-Type: application/json; charset=utf-8');
    }

    echo \json_encode(
        [
            'code'    => $throwable->getCode(),
            'message' => 'An error occurred while executing the application: ' . $throwable->getMessage(),
            'error'   => true,
        ]
    );

    exit;
}
