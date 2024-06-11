<?php

/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Providers;

use InfluxDB2\Client;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\StatsServer\Repositories\InfluxdbRepository;
use Joomla\StatsServer\Repositories\StatisticsRepository;

/**
 * Repository service provider
 */
class RepositoryServiceProvider implements ServiceProviderInterface
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
        $container->share(StatisticsRepository::class, [$this, 'getStatisticsRepositoryService']);
        $container->share(InfluxdbRepository::class, [$this, 'getInfluxdbRepositoryService']);
    }

    /**
     * Get the StatisticsRepository service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  StatisticsRepository
     */
    public function getStatisticsRepositoryService(Container $container): StatisticsRepository
    {
        return new StatisticsRepository(
            $container->get(DatabaseInterface::class)
        );
    }

    /**
     * Get the StatisticsRepository service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  StatisticsRepository
     */
    public function getInfluxdbRepositoryService(Container $container): InfluxdbRepository
    {
        return new InfluxdbRepository(
            $container->get(Client::class)
        );
    }
}
