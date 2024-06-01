<?php

/**
 * Joomla! Statistics Server
 *
 * @copyright  Copyright (C) 2013 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Joomla\StatsServer\EventListener;

use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationErrorEvent;
use Joomla\Application\WebApplication;
use Joomla\Console\ConsoleEvents;
use Joomla\Console\Event\ApplicationErrorEvent as ConsoleApplicationErrorEvent;
use Joomla\Event\SubscriberInterface;
use Joomla\Router\Exception\MethodNotAllowedException;
use Joomla\Router\Exception\RouteNotFoundException;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Error handling event subscriber
 */
class ErrorSubscriber implements SubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ApplicationEvents::ERROR         => 'handleWebError',
            ConsoleEvents::APPLICATION_ERROR => 'handleConsoleError',
        ];
    }

    /**
     * Handle console application errors.
     *
     * @param   ConsoleApplicationErrorEvent  $event  Event object
     *
     * @return  void
     */
    public function handleConsoleError(ConsoleApplicationErrorEvent $event): void
    {
        $this->logger->error(
            sprintf('Uncaught Throwable of type %s caught.', \get_class($event->getError())),
            ['exception' => $event->getError()]
        );

        (new SymfonyStyle($event->getApplication()->getConsoleInput(), $event->getApplication()->getConsoleOutput()))
            ->error(sprintf('Uncaught Throwable of type %s caught: %s', \get_class($event->getError()), $event->getError()->getMessage()));
    }

    /**
     * Handle web application errors.
     *
     * @param   ApplicationErrorEvent  $event  Event object
     *
     * @return  void
     */
    public function handleWebError(ApplicationErrorEvent $event): void
    {
        $app = $event->getApplication();

        switch (true) {
            case $event->getError() instanceof MethodNotAllowedException:
                // Log the error for reference
                $this->logger->error(
                    sprintf('Route `%s` not supported by method `%s`', $app->get('uri.route'), $app->getInput()->getMethod()),
                    ['exception' => $event->getError()]
                );

                $this->prepareResponse($event);

                $app->setHeader('Allow', implode(', ', $event->getError()->getAllowedMethods()));

                break;

            case $event->getError() instanceof RouteNotFoundException:
                // Log the error for reference
                $this->logger->error(
                    sprintf('Route `%s` not found', $app->get('uri.route')),
                    ['exception' => $event->getError()]
                );

                $this->prepareResponse($event);

                break;

            default:
                $this->logger->error(
                    sprintf('Uncaught Throwable of type %s caught.', \get_class($event->getError())),
                    ['exception' => $event->getError()]
                );

                $this->prepareResponse($event);

                break;
        }
    }

    /**
     * Prepare the response for the event
     *
     * @param   ApplicationErrorEvent  $event  Event object
     *
     * @return  void
     */
    private function prepareResponse(ApplicationErrorEvent $event): void
    {
        /** @var WebApplication $app */
        $app = $event->getApplication();

        $app->allowCache(false);

        $data = [
            'code'    => $event->getError()->getCode(),
            'message' => $event->getError()->getMessage(),
            'error'   => true,
        ];

        $response = new JsonResponse($data);

        switch ($event->getError()->getCode()) {
            case 404:
                $response = $response->withStatus(404);

                break;

            case 405:
                $response = $response->withStatus(405);

                break;

            case 500:
            default:
                $response = $response->withStatus(500);

                break;
        }

        $app->setResponse($response);
    }
}
