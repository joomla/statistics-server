<?php

/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Providers;

use Joomla\Database\DatabaseDriver;
use Joomla\Database\Monitor\LoggingMonitor;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\StatsServer\Database\Migrations;

/**
 * Database service provider
 */
class DatabaseServiceProvider implements ServiceProviderInterface
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
        $container->extend(DatabaseDriver::class, [$this, 'extendDatabaseDriverService']);

        $container->alias('db.monitor.logging', LoggingMonitor::class)
            ->share(LoggingMonitor::class, [$this, 'getDbMonitorLoggingService']);

        $container->alias('db.migrations', Migrations::class)
            ->share(Migrations::class, [$this, 'getDbMigrationsService']);
    }

    /**
     * Extends the database driver service
     *
     * @param   DatabaseDriver  $db         The database driver to extend.
     * @param   Container       $container  The DI container.
     *
     * @return  DatabaseDriver
     */
    public function extendDatabaseDriverService(DatabaseDriver $db, Container $container): DatabaseDriver
    {
        $db->setMonitor($container->get(LoggingMonitor::class));

        return $db;
    }

    /**
     * Get the `db.migrations` service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  Migrations
     */
    public function getDbMigrationsService(Container $container): Migrations
    {
        return new Migrations(
            $container->get(DatabaseDriver::class),
            $container->get('filesystem.migrations')
        );
    }

    /**
     * Get the `db.monitor.logging` service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  LoggingMonitor
     */
    public function getDbMonitorLoggingService(Container $container): LoggingMonitor
    {
        $monitor = new LoggingMonitor();
        $monitor->setLogger($container->get('monolog.logger.database'));

        return $monitor;
    }
}
