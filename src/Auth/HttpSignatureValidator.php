<?php
namespace ActivityPub\Auth;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * The HttpSignatureValidator is a subscriber to the kernel.request event
 * that validates HTTP signatures if present.
 *
 */
class HttpSignatureValidator implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => 'validateHttpSignature'
        );
    }

    /**
     * Check for a valid HTTP signature on the request. If the request has a valid signature,
     * set the 'signed' and 'signedBy' keys on the request ('signedBy' is the id of the actor
     * whose key signed the request)
     */
    public function validateHttpSignature( GetResponseEvent $event )
    {
        $request = $event->getRequest();
    }
}
?>
