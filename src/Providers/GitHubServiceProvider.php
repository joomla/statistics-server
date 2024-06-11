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
use Joomla\Github\Github as BaseGithub;
use Joomla\StatsServer\GitHub\GitHub;

/**
 * GitHub service provider
 */
class GitHubServiceProvider implements ServiceProviderInterface
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
        $container->alias('github', BaseGithub::class)
            ->alias(GitHub::class, BaseGithub::class)
            ->share(BaseGithub::class, [$this, 'getGithubService']);
    }

    /**
     * Get the `github` service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  GitHub
     */
    public function getGithubService(Container $container): GitHub
    {
        /** @var \Joomla\Registry\Registry $config */
        $config = $container->get('config');

        return new GitHub($config->extract('github'));
    }
}
