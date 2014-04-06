<?php

/**
 * Property Privately API
 *
 * (c) Elliot Wright, 2014 <wright.elliot@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SeerUK\RestBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use SeerUK\RestBundle\HttpFoundation\HalJsonResponse;
use SeerUK\RestBundle\Wrapper\Exception\ExceptionWrapper;

/**
 * Exception Listener
 */
class ExceptionListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Set up Exception Listener
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Hijack Exception Handler
     *
     * @param  GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $wrapper   = new ExceptionWrapper($exception);
        $response  = new HalJsonResponse();
        $response->setData($wrapper->toArray());

        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
        } else {
            $response->setStatusCode(HalJsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->logger->error(sprintf(
            '"%s" with message "%s" on line %d in %s.',
            get_class($exception),
            $exception->getMessage(),
            $exception->getLine(),
            $exception->getFile()
        ));

        $event->setResponse($response);
    }
}