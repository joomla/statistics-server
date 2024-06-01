<?php

/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Providers;

use Joomla\Console\Application;
use Joomla\Console\Loader\ContainerLoader;
use Joomla\Console\Loader\LoaderInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\Command\DebugEventDispatcherCommand;
use Joomla\Event\DispatcherInterface;
use Joomla\Router\Command\DebugRouterCommand;
use Joomla\Router\Router;
use Joomla\StatsServer\Commands\Database\MigrateCommand;
use Joomla\StatsServer\Commands\Database\MigrationStatusCommand;
use Joomla\StatsServer\Commands\SnapshotCommand;
use Joomla\StatsServer\Commands\SnapshotRecentlyUpdatedCommand;
use Joomla\StatsServer\Commands\Tags\FetchJoomlaTagsCommand;
use Joomla\StatsServer\Commands\Tags\FetchPhpTagsCommand;
use Joomla\StatsServer\Commands\UpdateCommand;
use Joomla\StatsServer\Database\Migrations;
use Joomla\StatsServer\GitHub\GitHub;
use Joomla\StatsServer\Views\Stats\StatsJsonView;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Console service provider
 */
class ConsoleServiceProvider implements ServiceProviderInterface
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
        $container->share(Application::class, [$this, 'getConsoleApplicationService']);

        /*
         * Application Helpers and Dependencies
         */

        $container->alias(ContainerLoader::class, LoaderInterface::class)
            ->share(LoaderInterface::class, [$this, 'getCommandLoaderService']);

        /*
         * Commands
         */

        $container->share(DebugEventDispatcherCommand::class, [$this, 'getDebugEventDispatcherCommandService']);
        $container->share(DebugRouterCommand::class, [$this, 'getDebugRouterCommandService']);
        $container->share(MigrateCommand::class, [$this, 'getDatabaseMigrateCommandService']);
        $container->share(MigrationStatusCommand::class, [$this, 'getDatabaseMigrationStatusCommandService']);
        $container->share(FetchJoomlaTagsCommand::class, [$this, 'getFetchJoomlaTagsCommandService']);
        $container->share(FetchPhpTagsCommand::class, [$this, 'getFetchPhpTagsCommandService']);
        $container->share(SnapshotCommand::class, [$this, 'getSnapshotCommandService']);
        $container->share(SnapshotRecentlyUpdatedCommand::class, [$this, 'getSnapshotRecentlyUpdatedCommandService']);
        $container->share(UpdateCommand::class, [$this, 'getUpdateCommandService']);
    }

    /**
     * Get the LoaderInterface service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  LoaderInterface
     */
    public function getCommandLoaderService(Container $container): LoaderInterface
    {
        $mapping = [
            DebugEventDispatcherCommand::getDefaultName()    => DebugEventDispatcherCommand::class,
            DebugRouterCommand::getDefaultName()             => DebugRouterCommand::class,
            MigrationStatusCommand::getDefaultName()         => MigrationStatusCommand::class,
            MigrateCommand::getDefaultName()                 => MigrateCommand::class,
            FetchJoomlaTagsCommand::getDefaultName()         => FetchJoomlaTagsCommand::class,
            FetchPhpTagsCommand::getDefaultName()            => FetchPhpTagsCommand::class,
            SnapshotCommand::getDefaultName()                => SnapshotCommand::class,
            SnapshotRecentlyUpdatedCommand::getDefaultName() => SnapshotRecentlyUpdatedCommand::class,
            UpdateCommand::getDefaultName()                  => UpdateCommand::class,
        ];

        return new ContainerLoader($container, $mapping);
    }

    /**
     * Get the console Application service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  Application
     */
    public function getConsoleApplicationService(Container $container): Application
    {
        $application = new Application(new ArgvInput(), new ConsoleOutput(), $container->get('config'));

        $application->setCommandLoader($container->get(LoaderInterface::class));
        $application->setDispatcher($container->get(DispatcherInterface::class));
        $application->setLogger($container->get(LoggerInterface::class));
        $application->setName('Joomla! Statistics Server');

        return $application;
    }

    /**
     * Get the MigrateCommand service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  MigrateCommand
     */
    public function getDatabaseMigrateCommandService(Container $container): MigrateCommand
    {
        $command = new MigrateCommand($container->get(Migrations::class));
        $command->setLogger($container->get(LoggerInterface::class));

        return $command;
    }

    /**
     * Get the MigrationStatusCommand service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  MigrationStatusCommand
     */
    public function getDatabaseMigrationStatusCommandService(Container $container): MigrationStatusCommand
    {
        return new MigrationStatusCommand($container->get(Migrations::class));
    }

    /**
     * Get the DebugEventDispatcherCommand service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  DebugEventDispatcherCommand
     */
    public function getDebugEventDispatcherCommandService(Container $container): DebugEventDispatcherCommand
    {
        return new DebugEventDispatcherCommand($container->get(DispatcherInterface::class));
    }

    /**
     * Get the DebugRouterCommand service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  DebugRouterCommand
     */
    public function getDebugRouterCommandService(Container $container): DebugRouterCommand
    {
        return new DebugRouterCommand($container->get(Router::class));
    }

    /**
     * Get the FetchJoomlaTagsCommand service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  FetchJoomlaTagsCommand
     */
    public function getFetchJoomlaTagsCommandService(Container $container): FetchJoomlaTagsCommand
    {
        return new FetchJoomlaTagsCommand($container->get(GitHub::class), $container->get('filesystem.versions'));
    }

    /**
     * Get the FetchPhpTagsCommand class service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  FetchPhpTagsCommand
     */
    public function getFetchPhpTagsCommandService(Container $container): FetchPhpTagsCommand
    {
        return new FetchPhpTagsCommand($container->get(GitHub::class), $container->get('filesystem.versions'));
    }

    /**
     * Get the SnapshotCommand service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  SnapshotCommand
     */
    public function getSnapshotCommandService(Container $container): SnapshotCommand
    {
        return new SnapshotCommand($container->get(StatsJsonView::class), $container->get('filesystem.snapshot'));
    }

    /**
     * Get the SnapshotRecentlyUpdatedCommand service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  SnapshotRecentlyUpdatedCommand
     */
    public function getSnapshotRecentlyUpdatedCommandService(Container $container): SnapshotRecentlyUpdatedCommand
    {
        return new SnapshotRecentlyUpdatedCommand($container->get(StatsJsonView::class), $container->get('filesystem.snapshot'));
    }

    /**
     * Get the UpdateCommand class service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  UpdateCommand
     */
    public function getUpdateCommandService(Container $container): UpdateCommand
    {
        return new UpdateCommand();
    }
}
