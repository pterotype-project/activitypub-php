<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace ActivityPub\Auth;

use ActivityPub\Objects\ObjectsService;
use Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * The AuthListener class answers the question, "is this request authorized
 * to act on behalf of this Actor?"
 *
 * It delegates most of the work to a passed-in Callable to allow library clients to
 * plug in their own authentication methods.
 */
class AuthListener implements EventSubscriberInterface
{
    /**
     * The Callable that is called to determine if a request is authorized for an Actor
     *
     * @var Callable
     *
     */
    private $authFunction;

    /**
     * @var ObjectsService
     */
    private $objectsService;

    /**
     * Constructs a new AuthenticationService
     *
     * @param Callable $authFunction A Callable that should accept
     * @param ObjectsService $objectsService
     */
    public function __construct( Callable $authFunction, ObjectsService $objectsService )
    {
        $this->authFunction = $authFunction;
        $this->objectsService = $objectsService;
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => 'checkAuth'
        );
    }

    public function checkAuth( GetResponseEvent $event )
    {
        $request = $event->getRequest();
        if ( $request->attributes->has( 'actor' ) ) {
            return;
        }
        $actorId = call_user_func( $this->authFunction );
        if ( $actorId && !empty( $actorId ) ) {
            $actor = $this->objectsService->dereference( $actorId );
            if ( !$actor ) {
                throw new Exception( "Actor $actorId does not exist" );
            }
            $request->attributes->set( 'actor', $actor );
        }
    }
}

