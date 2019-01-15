<?php
namespace ActivityPub\Auth;

use ActivityPub\Crypto\HttpSignatureService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * The SignatureListener is a subscriber to the kernel.request event
 * that validates HTTP signatures if present.
 *
 */
class SignatureListener implements EventSubscriberInterface
{
    /**
     * @var HttpSignatureService
     */
    private $httpSignatureService;
    
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => 'validateHttpSignature'
        );
    }

    /**
     * Check for a valid HTTP signature on the request. If the request has a valid 
     * signature, set the 'signed' and 'signedBy' keys on the request ('signedBy' is 
     * the id of the actor whose key signed the request)
     */
    public function validateHttpSignature( GetResponseEvent $event )
    {
        $request = $event->getRequest();
    }
}
?>
