<?php
/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Kernel;

use Joomla\Application\AbstractApplication;
use Joomla\Application\AbstractWebApplication;
use Joomla\DI\Container;
use Joomla\StatsServer\Kernel;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * Web application kernel
 */
class WebKernel extends Kernel
{
	/**
	 * Build the service container
	 *
	 * @return  Container
	 */
	protected function buildContainer(): Container
	{
		$container = parent::buildContainer();

		// Alias the web application to Joomla's base application class as this is the primary application for the environment
		$container->alias(AbstractApplication::class, AbstractWebApplication::class);

		// Alias the web application logger as the primary logger for the environment
		$container->alias('monolog', 'monolog.logger.application')
			->alias('logger', 'monolog.logger.application')
			->alias(Logger::class, 'monolog.logger.application')
			->alias(LoggerInterface::class, 'monolog.logger.application');

		// Set error reporting based on config
		$errorReporting = (int) $container->get('config')->get('errorReporting', 0);
		error_reporting($errorReporting);

		return $container;
	}
}
