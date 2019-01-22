<?php
namespace ActivityPub\Controllers;

use ActivityPub\Activities\InboxActivityEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * The InboxController handles POST requests to an inbox
 */
class InboxController
{
    private $eventDispatcher;

    public function __construct( EventDispatcher $EventDispatcher )
    {
        $this->eventDispatcher = $EventDispatcher;
    }

    /**
     * Handles the inbox request and returns a proper Response
     *
     * @param Request $request The request
     * @return Response
     */
    public function handle( Request $request )
    {
        if ( ! $request->attributes->has( 'actor' ) ) {
            throw new UnauthorizedHttpException();
        }
        $actor = $request->attributes->get( 'actor' );
        $inboxId = $this->getUriWithoutQuery( $request );
        if ( ! $actor->hasField( 'inbox' ) || $actor['inbox']['id'] !== $inboxId ) {
            throw new UnauthorizedHttpException(); 
        }
        $activity = $request->attributes->get( 'activity' );
        $event = new InboxActivityEvent( $activity, $actor );
        $this->eventDispatcher->dispatch( InboxActivityEvent::NAME, $event );
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
