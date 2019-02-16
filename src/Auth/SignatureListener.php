<?php

namespace ActivityPub\Auth;

use ActivityPub\Crypto\HttpSignatureService;
use ActivityPub\Objects\ObjectsService;
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

    /**
     * @var ObjectsService
     */
    private $objectsService;

    public function __construct( HttpSignatureService $httpSignatureService,
                                 ObjectsService $objectsService )
    {
        $this->httpSignatureService = $httpSignatureService;
        $this->objectsService = $objectsService;
    }

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
     * @param GetResponseEvent $event
     */
    public function validateHttpSignature( GetResponseEvent $event )
    {
        $request = $event->getRequest();
        $headers = $request->headers;
        $signatureHeader = null;
        if ( $headers->has( 'signature' ) ) {
            $signatureHeader = $headers->get( 'signature' );
        } else if ( $headers->has( 'authorization' ) &&
            substr( $headers->get( 'authorization' ), 0, 9 ) === 'Signature' ) {
            $signatureHeader = substr( $headers->get( 'authorization' ), 10 );
        }
        if ( !$signatureHeader ) {
            return;
        }
        $matches = array();
        if ( !preg_match( '/keyId="([^"]*)"/', $signatureHeader, $matches ) ) {
            return;
        }
        $keyId = $matches[1];
        $key = $this->objectsService->dereference( $keyId );
        if ( !$key || !$key->hasField( 'owner' ) || !$key->hasField( 'publicKeyPem' ) ) {
            return;
        }
        $owner = $key['owner'];
        if ( is_string( $owner ) ) {
            $owner = $this->objectsService->dereference( $owner );
        }
        if ( !$owner ) {
            return;
        }
        if ( !$this->httpSignatureService->verify( $request, $key['publicKeyPem'] ) ) {
            return;
        }
        $request->attributes->set( 'signed', true );
        if ( !$request->attributes->has( 'actor' ) ) {
            $request->attributes->set( 'actor', $owner );
        }
    }
}

