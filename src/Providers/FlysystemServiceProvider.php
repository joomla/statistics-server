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
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

/**
 * Flysystem service provider
 */
class FlysystemServiceProvider implements ServiceProviderInterface
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
        $container->share('filesystem.migrations', [$this, 'getMigrationsFilesystemService']);
        $container->share('filesystem.snapshot', [$this, 'getSnapshotFilesystemService']);
        $container->share('filesystem.versions', [$this, 'getVersionsFilesystemService']);
    }

    /**
     * Get the `filesystem.migrations` service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  Filesystem
     */
    public function getMigrationsFilesystemService(Container $container): Filesystem
    {
        return new Filesystem(new Local(APPROOT . '/etc/migrations'));
    }

    /**
     * Get the `filesystem.snapshot` service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  Filesystem
     */
    public function getSnapshotFilesystemService(Container $container): Filesystem
    {
        return new Filesystem(new Local(APPROOT . '/snapshots'));
    }

    /**
     * Get the `filesystem.versions` service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  Filesystem
     */
    public function getVersionsFilesystemService(Container $container): Filesystem
    {
        return new Filesystem(new Local(APPROOT . '/versions'));
    }
}
