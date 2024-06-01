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
use Joomla\Event\Dispatcher;
use Joomla\Event\DispatcherInterface;
use Joomla\StatsServer\EventListener\AnalyticsSubscriber;
use Joomla\StatsServer\EventListener\ErrorSubscriber;
use Psr\Log\LoggerInterface;
use TheIconic\Tracking\GoogleAnalytics\Analytics;

/**
 * Event service provider
 */
class EventServiceProvider implements ServiceProviderInterface
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
        $container->alias(Dispatcher::class, DispatcherInterface::class)
            ->share(DispatcherInterface::class, [$this, 'getDispatcherService']);

        $container->share(AnalyticsSubscriber::class, [$this, 'getAnalyticsSubscriberService'])
            ->tag('event.subscriber', [AnalyticsSubscriber::class]);

        $container->share(ErrorSubscriber::class, [$this, 'getErrorSubscriberService'])
            ->tag('event.subscriber', [ErrorSubscriber::class]);
    }

    /**
     * Get the AnalyticsSubscriber service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  AnalyticsSubscriber
     */
    public function getAnalyticsSubscriberService(Container $container): AnalyticsSubscriber
    {
        $subscriber = new AnalyticsSubscriber($container->get(Analytics::class));
        $subscriber->setLogger($container->get(LoggerInterface::class));

        return $subscriber;
    }

    /**
     * Get the DispatcherInterface service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  DispatcherInterface
     */
    public function getDispatcherService(Container $container): DispatcherInterface
    {
        $dispatcher = new Dispatcher();

        foreach ($container->getTagged('event.subscriber') as $subscriber) {
            $dispatcher->addSubscriber($subscriber);
        }

        return $dispatcher;
    }

    /**
     * Get the ErrorSubscriber service
     *
     * @param   Container  $container  The DI container.
     *
     * @return  ErrorSubscriber
     */
    public function getErrorSubscriberService(Container $container): ErrorSubscriber
    {
        $subscriber = new ErrorSubscriber();
        $subscriber->setLogger($container->get(LoggerInterface::class));

        return $subscriber;
    }
}
