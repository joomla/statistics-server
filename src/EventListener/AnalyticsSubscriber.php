<?php

/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\EventListener;

use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationEvent;
use Joomla\Application\WebApplicationInterface;
use Joomla\Event\SubscriberInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ramsey\Uuid\Uuid;
use TheIconic\Tracking\GoogleAnalytics\Analytics;

/**
 * Analytics handling event subscriber
 */
class AnalyticsSubscriber implements SubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Application analytics object.
     *
     * @var  Analytics
     */
    private $analytics;

    /**
     * Constructor.
     *
     * @param   Analytics  $analytics  Application analytics object.
     */
    public function __construct(Analytics $analytics)
    {
        $this->analytics = $analytics;
    }

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ApplicationEvents::BEFORE_EXECUTE => 'onBeforeExecute',
        ];
    }

    /**
     * Logs the visit to analytics if able.
     *
     * @param   ApplicationEvent  $event  Event object
     *
     * @return  void
     */
    public function onBeforeExecute(ApplicationEvent $event): void
    {
        $app = $event->getApplication();

        if (!($app instanceof WebApplicationInterface)) {
            return;
        }

        // On a GET request to the live domain, submit analytics data
        if (
            $app->getInput()->getMethod() !== 'GET'
            || strpos($app->getInput()->server->getString('HTTP_HOST', ''), 'developer.joomla.org') !== 0
        ) {
            return;
        }

        $this->analytics->setAsyncRequest(true)
            ->setProtocolVersion('1')
            ->setTrackingId('UA-544070-16')
            ->setClientId(Uuid::uuid4()->toString())
            ->setDocumentPath($app->get('uri.base.path'))
            ->setIpOverride($app->getInput()->server->getString('REMOTE_ADDR', '127.0.0.1'))
            ->setUserAgentOverride($app->getInput()->server->getString('HTTP_USER_AGENT', 'JoomlaStats/1.0'));

        // Don't allow sending Analytics data to cause a failure
        try {
            $this->analytics->sendPageview();
        } catch (\Exception $e) {
            // Log the error for reference
            $this->logger->error(
                'Error sending analytics data.',
                ['exception' => $e]
            );
        }
    }
}
