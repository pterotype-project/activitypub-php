<?php
namespace ActivityPub\Controllers;

use ActivityPub\Activities\OutboxActivityEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class OutboxController
{
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    public function __construct( EventDispatcher $eventDispatcher )
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Handles the outbox request and returns a proper response
     *
     * @param Request $request The incoming request
     * @return Response
     */
    public function handle( Request $request )
    {
        if ( ! $request->attributes->has( 'actor' ) ) {
            throw new UnauthorizedHttpException();
        }
        $actor = $request->attributes->get( 'actor' );
        $outboxId = $this->getUriWithoutQuery( $request );
        if ( ! $actor->hasField( 'outbox' ) || $actor['outbox']['id'] !== $outboxId ) {
            throw new UnauthorizedHttpException(); 
        }
        $activity = $request->attributes->get( 'activity' );
        $event = new OutboxActivityEvent( $activity, $actor, $request );
        $this->eventDispatcher->dispatch( OutboxActivityEvent::NAME, $event );
    }

    private function getUriWithoutQuery( Request $request )
    {
        $uri = $request->getUri();
        $queryPos = strpos( $uri, '?' );
        if ( $queryPos !== false ) {
            $uri = substr( $uri, 0, $queryPos );
        }
        return $uri;
    }
}
?>
