<?php
/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer;

use Joomla\Application\AbstractApplication;
use Joomla\Database\Service\DatabaseProvider;
use Joomla\DI\Container;
use Joomla\DI\ContainerAwareInterface;
use Joomla\DI\ContainerAwareTrait;
use Joomla\Registry\Registry;
use Joomla\StatsServer\Providers\AnalyticsServiceProvider;
use Joomla\StatsServer\Providers\ConsoleServiceProvider;
use Joomla\StatsServer\Providers\DatabaseServiceProvider;
use Joomla\StatsServer\Providers\EventServiceProvider;
use Joomla\StatsServer\Providers\FlysystemServiceProvider;
use Joomla\StatsServer\Providers\GitHubServiceProvider;
use Joomla\StatsServer\Providers\MonologServiceProvider;
use Joomla\StatsServer\Providers\RepositoryServiceProvider;
use Joomla\StatsServer\Providers\WebApplicationServiceProvider;
use Monolog\ErrorHandler;
use Monolog\Logger;

/**
 * Application kernel
 */
abstract class Kernel implements ContainerAwareInterface
{
	use ContainerAwareTrait;

	/**
	 * Flag indicating this Kernel has been booted
	 *
	 * @var  boolean
	 */
	protected $booted = false;

	/**
	 * Boot the Kernel
	 *
	 * @return  void
	 */
	public function boot(): void
	{
		if ($this->booted)
		{
			return;
		}

		$this->setContainer($this->buildContainer());

		// Register deprecation logging via Monolog
		ErrorHandler::register($this->getContainer()->get(Logger::class), [E_DEPRECATED, E_USER_DEPRECATED], false, false);

		$this->booted = true;
	}

	/**
	 * Check if the Kernel is booted
	 *
	 * @return  boolean
	 */
	public function isBooted(): bool
	{
		return $this->booted;
	}

	/**
	 * Run the kernel
	 *
	 * @return  void
	 */
	public function run(): void
	{
		$this->boot();

		if (!$this->getContainer()->has(AbstractApplication::class))
		{
			throw new \RuntimeException('The application has not been registered with the container.');
		}

		$this->getContainer()->get(AbstractApplication::class)->execute();
	}

	/**
	 * Build the service container
	 *
	 * @return  Container
	 */
	protected function buildContainer(): Container
	{
		$config = $this->loadConfiguration();

		$container = new Container;
		$container->share('config', $config);

		$container->registerServiceProvider(new AnalyticsServiceProvider)
			->registerServiceProvider(new ConsoleServiceProvider)
			->registerServiceProvider(new DatabaseProvider)
			->registerServiceProvider(new DatabaseServiceProvider)
			->registerServiceProvider(new EventServiceProvider)
			->registerServiceProvider(new FlysystemServiceProvider)
			->registerServiceProvider(new GitHubServiceProvider)
			->registerServiceProvider(new MonologServiceProvider)
			->registerServiceProvider(new RepositoryServiceProvider)
			->registerServiceProvider(new WebApplicationServiceProvider);

        $container->share(
                \InfluxDB2\Client::class,
                function (Container $container) {
                    /** @var \Joomla\Registry\Registry $config */
                    $config  = $container->get('config');
                    $options = (array) $config->get('influxdb');

                    return new \InfluxDB2\Client($options);
                }
            );


		return $container;
	}

	/**
	 * Load the application's configuration
	 *
	 * @return  Registry
	 */
	private function loadConfiguration(): Registry
	{
		$registry = new Registry;
		$registry->loadFile(APPROOT . '/etc/config.dist.json');

		if (file_exists(APPROOT . '/etc/config.json'))
		{
			$registry->loadFile(APPROOT . '/etc/config.json');
		}

		return $registry;
	}
}
