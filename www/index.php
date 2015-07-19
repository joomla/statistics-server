<?php

// Application constants
define('APPROOT',      dirname(__DIR__));
define("JPATH_TEMPLATES", APPROOT . "/src/templates");

// Ensure we've initialized Composer
if (!file_exists(APPROOT . '/vendor/autoload.php'))
{
	header('HTTP/1.1 500 Internal Server Error', null, 500);
	echo 'Composer is not set up properly, please run "composer install".';
	exit;
}

$container = (new Joomla\DI\Container)
	->registerServiceProvider(new Stats\Providers\ConfigServiceProvider(APPROOT . "/etc/config.json"))
	->registerServiceProvider(new Stats\Providers\DatabaseServiceProvider);

$app = $container->alias('app', 'Stats\Application')->buildObject('Stats\Application');
$container->registerServiceProvider(new Stats\Providers\TwigServiceProvider);
$app->setContainer($container);

$router = (new Stats\Router($app->input))
	->setControllerPrefix("Stats\\Controllers\\")
	->setDefaultController("DisplayController")
	->addMap("/submit", "SubmitController");

$app
	->setContainer($container)
	->setRouter($router)
	->execute();
