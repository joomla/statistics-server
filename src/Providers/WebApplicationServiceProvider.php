<?php

/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\Providers;

use Joomla\Application\AbstractApplication;
use Joomla\Application\AbstractWebApplication;
use Joomla\Application\Controller\ContainerControllerResolver;
use Joomla\Application\Controller\ControllerResolverInterface;
use Joomla\Application\Web\WebClient;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Input\Input;
use Joomla\Router\Router;
use Joomla\StatsServer\Controllers\DisplayStatisticsController;
use Joomla\StatsServer\Controllers\SubmitDataController;
use Joomla\StatsServer\Repositories\InfluxdbRepository;
use Joomla\StatsServer\Repositories\StatisticsRepository;
use Joomla\StatsServer\Views\Stats\StatsJsonView;
use Joomla\StatsServer\WebApplication;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Log\LoggerInterface;

/**
 * Web application service provider
 */
class WebApplicationServiceProvider implements ServiceProviderInterface
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
        $container->alias(WebApplication::class, AbstractWebApplication::class)
            ->share(AbstractWebApplication::class, [$this, 'getWebApplicationService']);

        /*
         * Application Class Dependencies
         */

        $container->share(Input::class, [$this, 'getInputService']);
        $container->share(Router::class, [$this, 'getRouterService']);

        $container->alias(ContainerControllerResolver::class, ControllerResolverInterface::class)
            ->share(ControllerResolverInterface::class, [$this, 'getControllerResolverService']);

        $container->share(WebClient::class, [$this, 'getWebClientService']);

        /*
         * MVC Layer
         */

        // Controllers
        $container->share(DisplayStatisticsController::class, [$this, 'getDisplayStatisticsControllerService']);
        $container->share(SubmitDataController::class, [$this, 'getSubmitDataControllerService']);

        // Views
        $container->share(StatsJsonView::class, [$this, 'getStatsJsonViewService']);
    }

    /**
     * Get the controller resolver service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  ControllerResolverInterface
     */
    public function getControllerResolverService(Container $container): ControllerResolverInterface
    {
        return new ContainerControllerResolver($container);
    }

    /**
     * Get the DisplayControllerGet class service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  DisplayStatisticsController
     */
    public function getDisplayStatisticsControllerService(Container $container): DisplayStatisticsController
    {
        $controller = new DisplayStatisticsController(
            $container->get(StatsJsonView::class)
        );

        $controller->setApplication($container->get(AbstractApplication::class));
        $controller->setInput($container->get(Input::class));

        return $controller;
    }

    /**
     * Get the Input class service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  Input
     */
    public function getInputService(Container $container): Input
    {
        return new Input($_REQUEST);
    }

    /**
     * Get the router service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  Router
     */
    public function getRouterService(Container $container): Router
    {
        $router = new Router();

        $router->get(
            '/',
            DisplayStatisticsController::class
        );

        $router->post(
            '/submit',
            SubmitDataController::class
        );

        $router->get(
            '/:source',
            DisplayStatisticsController::class,
            [
                'source' => '(' . implode('|', StatisticsRepository::ALLOWED_SOURCES) . ')',
            ]
        );

        return $router;
    }

    /**
     * Get the StatsJsonView class service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  StatsJsonView
     */
    public function getStatsJsonViewService(Container $container): StatsJsonView
    {
        return new StatsJsonView(
            $container->get(StatisticsRepository::class)
        );
    }

    /**
     * Get the SubmitControllerCreate class service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  SubmitDataController
     */
    public function getSubmitDataControllerService(Container $container): SubmitDataController
    {
        $controller = new SubmitDataController(
            $container->get(StatisticsRepository::class),
            $container->get('filesystem.versions'),
            $container->get(InfluxdbRepository::class),
        );

        $controller->setApplication($container->get(AbstractApplication::class));
        $controller->setInput($container->get(Input::class));

        return $controller;
    }

    /**
     * Get the web application service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  WebApplication
     */
    public function getWebApplicationService(Container $container): WebApplication
    {
        $application = new WebApplication(
            $container->get(ControllerResolverInterface::class),
            $container->get(Router::class),
            $container->get(Input::class),
            $container->get('config'),
            $container->get(WebClient::class),
            new JsonResponse([])
        );

        // Inject extra services
        $application->setDispatcher($container->get(DispatcherInterface::class));
        $application->setLogger($container->get(LoggerInterface::class));

        return $application;
    }

    /**
     * Get the web client service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  WebClient
     */
    public function getWebClientService(Container $container): WebClient
    {
        /** @var Input $input */
        $input          = $container->get(Input::class);
        $userAgent      = $input->server->getString('HTTP_USER_AGENT', '');
        $acceptEncoding = $input->server->getString('HTTP_ACCEPT_ENCODING', '');
        $acceptLanguage = $input->server->getString('HTTP_ACCEPT_LANGUAGE', '');

        return new WebClient($userAgent, $acceptEncoding, $acceptLanguage);
    }
}
