<?php
declare(strict_types=1);

namespace DR\Review\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ContentSecurityPolicyResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly string $hostname)
    {
    }

    public function onResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        if ($response->headers->has("Content-Security-Policy")) {
            return;
        }

        // only allow content from own host.
        // allow image svg+xml
        // allow websocket to connect to any port.
        $policy = sprintf("default-src 'self'; img-src 'self' data:; object-src: 'none'; connect-src 'self' %s:*", $this->hostname);

        $response->headers->set("Content-Security-Policy", $policy);
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onResponse'];
    }
}
