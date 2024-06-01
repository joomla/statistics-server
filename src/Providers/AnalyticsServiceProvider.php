<?php

/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Providers;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use TheIconic\Tracking\GoogleAnalytics\Analytics;

/**
 * Analytics service provider
 */
class AnalyticsServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     */
    public function register(Container $container): void
    {
        $container->share(Analytics::class, [$this, 'getAnalyticsService']);
    }

    /**
     * Get the Analytics class service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  Analytics
     */
    public function getAnalyticsService(Container $container): Analytics
    {
        return new Analytics(true);
    }
}
